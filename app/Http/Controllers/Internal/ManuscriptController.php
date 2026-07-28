<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Keyword;
use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\ManuscriptVersion;
use App\Services\Cache\CacheAsideService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManuscriptController extends Controller
{
    public function __construct(
        private readonly FileStorageService $files,
        private readonly CacheAsideService $cache,
    ) {}

    /** Module 2 function 1: Search submissions. */
    public function index(Request $request)
    {
        $query = Manuscript::query()->with(['journal', 'author', 'currentVersion']);

        if ($authorId = $request->query('author_id')) {
            $query->where('author_id', $authorId);
        }
        if ($coAuthorId = $request->query('co_author_id')) {
            // Accepted co-authors only (rows in the `authors` table are only
            // created once a co-author invitation is Accepted).
            $query->whereHas('authors', fn ($q) => $q->where('user_id', $coAuthorId))
                ->where('author_id', '!=', $coAuthorId);
        }
        if ($journalId = $request->query('journal_id')) {
            $query->where('journal_id', $journalId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$search])
                  ->orWhereHas('authors.user', fn ($u) => $u->where('full_name', 'ilike', "%{$search}%"))
                  ->orWhereHas('keywords', fn ($k) => $k->where('keyword_text', 'ilike', "%{$search}%"));
            });
        }

        return response()->json($query->orderByDesc('id')->paginate($request->integer('per_page', 20)));
    }

    /** Module 2 function 2: Create new submission (draft). */
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'journal_id' => 'required|integer|exists:journals,id',
            'author_id' => 'required|integer|exists:users,id',
            'manuscript_type' => 'required|string',
            'title' => 'required|string',
            'abstract' => 'nullable|string',
            'keywords' => 'sometimes|array',
            'keywords.*' => 'string',
        ])->validate();

        $manuscript = DB::transaction(function () use ($data) {
            $manuscript = Manuscript::create([
                'journal_id' => $data['journal_id'],
                'author_id' => $data['author_id'],
                'manuscript_type' => $data['manuscript_type'],
                'title' => $data['title'],
                'abstract' => $data['abstract'] ?? null,
                'status' => 'Draft',
            ]);

            Author::create([
                'manuscript_id' => $manuscript->id,
                'user_id' => $data['author_id'],
                'author_order' => 1,
                'is_corresponding' => true,
            ]);

            foreach ($data['keywords'] ?? [] as $text) {
                $keyword = Keyword::firstOrCreate(['keyword_text' => $text]);
                $manuscript->keywords()->syncWithoutDetaching($keyword->id);
            }

            return $manuscript;
        });

        return response()->json($this->present($manuscript), 201);
    }

    public function show(int $id)
    {
        // Cache the plain array form, not the Eloquent model: caching the raw
        // model (with this many nested eager-loaded relations) round-trips
        // through Redis via PHP's native serialize()/unserialize(), which
        // reliably corrupts into a __PHP_Incomplete_Class on the very next
        // read within the same request lifecycle — confirmed reproducible on
        // every single cache hit, not just an occasional/stale-data edge
        // case. Arrays of scalars have no class-loading step, so this class
        // of corruption can't happen.
        $manuscript = $this->cache->remember(
            CacheAsideService::entityKey('manuscript', $id),
            fn () => Manuscript::with([
                'journal', 'author', 'authors.user', 'keywords', 'versions.files', 'versions.uploadedBy', 'currentVersion',
                'coAuthorInvitations.invitedAuthor', 'editorAssignments.editor',
                'reviewInvitations.reviewer', 'reviewInvitations.review.scores', 'reviewInvitations.review.files',
                'editorialDecisions.editor',
            ])->findOrFail($id)->toArray()
        );

        return response()->json($manuscript);
    }

    public function update(Request $request, int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        $data = Validator::make($request->all(), [
            'title' => 'sometimes|string',
            'abstract' => 'nullable|string',
            'manuscript_type' => 'sometimes|string',
            'status' => 'sometimes|string|in:'.implode(',', Manuscript::STATUSES),
        ])->validate();

        $manuscript->update($data);

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    /** Streams a manuscript file's bytes. No auth check here — Backend enforces who's allowed. */
    public function downloadFile(int $manuscriptId, int $fileId)
    {
        $file = ManuscriptFile::with('version')->findOrFail($fileId);

        if ($file->version->manuscript_id !== $manuscriptId || ! $this->files->exists($file->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return $this->files->stream($file->file_path, $file->original_filename);
    }

    /** Lets the primary author pick which uploaded version is the official one. Backend enforces who's allowed. */
    public function setMainVersion(int $id, int $versionId)
    {
        $manuscript = Manuscript::findOrFail($id);
        $version = ManuscriptVersion::findOrFail($versionId);

        if (! in_array($manuscript->status, ['Draft', 'Revision Required'], true)) {
            return response()->json(['message' => 'The main version can only be changed while the manuscript is in Draft or Revision Required status.'], 422);
        }

        if ($version->manuscript_id !== $manuscript->id) {
            return response()->json(['message' => 'Version does not belong to this manuscript.'], 422);
        }

        $manuscript->update(['current_version_id' => $version->id]);

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    /** Module 2 function 3 + 6: Upload manuscript file / revised version (versioned). */
    public function storeVersion(Request $request, int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        if (! in_array($manuscript->status, ['Draft', 'Revision Required'], true)) {
            return response()->json(['message' => 'Files can only be uploaded while the manuscript is in Draft or Revision Required status.'], 422);
        }

        $data = Validator::make($request->all(), [
            'response_note' => 'nullable|string',
            'uploaded_by' => 'required|integer|exists:users,id',
            'main_file' => 'required|file|mimes:pdf',
            'supplementary_files' => 'sometimes|array',
            'supplementary_files.*' => 'file',
        ])->validate();

        $version = DB::transaction(function () use ($manuscript, $data, $request) {
            $nextNumber = ($manuscript->versions()->max('version_number') ?? 0) + 1;

            $version = ManuscriptVersion::create([
                'manuscript_id' => $manuscript->id,
                'version_number' => $nextNumber,
                'file_path' => '',
                'response_note' => $data['response_note'] ?? null,
                'uploaded_by' => $data['uploaded_by'],
            ]);

            $mainPath = $this->files->storeManuscriptFile($manuscript->id, $version->id, $request->file('main_file'), 'main');
            $version->update(['file_path' => $mainPath]);
            ManuscriptFile::create([
                'version_id' => $version->id,
                'file_type' => 'main',
                'file_path' => $mainPath,
                'original_filename' => $request->file('main_file')->getClientOriginalName(),
                'size_kb' => (int) ceil($request->file('main_file')->getSize() / 1024),
            ]);

            foreach ($request->file('supplementary_files', []) as $file) {
                $path = $this->files->storeManuscriptFile($manuscript->id, $version->id, $file, 'supplementary');
                ManuscriptFile::create([
                    'version_id' => $version->id,
                    'file_type' => 'supplementary',
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'size_kb' => (int) ceil($file->getSize() / 1024),
                ]);
            }

            $isFirstVersion = $nextNumber === 1;
            $isAuthorUploader = (int) $data['uploaded_by'] === (int) $manuscript->author_id;
            $isRevisionRequired = $manuscript->status === 'Revision Required';

            if ($isFirstVersion || ($isRevisionRequired && $isAuthorUploader)) {
                $manuscript->update(['current_version_id' => $version->id]);
            }

            return $version;
        });

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($version->load('files'), 201);
    }

    /** Module 2 function 4: Invite co-authors (existing users only, per docx). */
    public function inviteCoAuthor(Request $request, int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        $data = Validator::make($request->all(), [
            'inviting_author_id' => 'required|integer|exists:users,id',
            'invited_author_id' => 'required|integer|exists:users,id',
        ])->validate();

        $existingActive = $manuscript->coAuthorInvitations()
            ->where('invited_author_id', $data['invited_author_id'])
            ->whereNotIn('status', ['Declined'])
            ->exists();

        if ($existingActive) {
            return response()->json(['message' => 'This user already has an active co-author invitation for this manuscript.'], 422);
        }

        $invitation = $manuscript->coAuthorInvitations()->create([
            'inviting_author_id' => $data['inviting_author_id'],
            'invited_author_id' => $data['invited_author_id'],
            'invited_at' => now(),
            'status' => 'Pending',
        ]);

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($invitation, 201);
    }

    /** Module 2 function 5: Submit for review. */
    public function submit(int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        if (! in_array($manuscript->status, ['Draft', 'Revision Required'], true)) {
            return response()->json(['message' => 'Only manuscripts in Draft or Revision Required status can be submitted.'], 422);
        }

        $manuscript->update(['status' => 'Submitted', 'submitted_at' => now()]);

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    /** Module 2 function 7: Withdraw submission. */
    public function withdraw(Request $request, int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        $data = Validator::make($request->all(), [
            'reason' => 'required|string',
        ])->validate();

        $manuscript->update([
            'status' => 'Withdrawn',
            'withdrawal_reason' => $data['reason'],
            'withdrawn_at' => now(),
        ]);

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    /** Reopens a Withdrawn manuscript back to Draft so the author can edit and resubmit it. */
    public function resubmit(int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        if ($manuscript->status !== 'Withdrawn') {
            return response()->json(['message' => 'Only Withdrawn manuscripts can be resubmitted.'], 422);
        }

        $manuscript->update(['status' => 'Draft']);

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    /** Author deletes a manuscript that never left Draft ("changed their mind" before ever submitting). */
    public function destroy(int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        if ($manuscript->status !== 'Draft') {
            return response()->json(['message' => 'Only Draft manuscripts can be deleted.'], 422);
        }

        $manuscript->delete();

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json(null, 204);
    }

    /** Admin archives a manuscript — if it was Published, unpublishes its article too so it disappears from public view. */
    public function archive(int $id)
    {
        $manuscript = Manuscript::findOrFail($id);

        if ($manuscript->status === 'Archived') {
            return response()->json($this->present($manuscript));
        }

        DB::transaction(function () use ($manuscript) {
            $wasPublished = $manuscript->status === 'Published';
            $manuscript->update(['pre_archive_status' => $manuscript->status, 'status' => 'Archived']);

            if ($wasPublished) {
                $manuscript->article()->update(['published_at' => null]);
            }
        });

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    /** Reverses archive() — puts the manuscript back in whatever status it held right before archiving. */
    public function restore(int $id)
    {
        $manuscript = Manuscript::findOrFail($id);
        $target = $manuscript->pre_archive_status ?: 'Submitted';

        DB::transaction(function () use ($manuscript, $target) {
            $manuscript->update(['status' => $target, 'pre_archive_status' => null]);

            if ($target === 'Published') {
                $manuscript->article()->update(['published_at' => now()]);
            }
        });

        $this->cache->forget(CacheAsideService::entityKey('manuscript', $id));

        return response()->json($this->present($manuscript->fresh()));
    }

    private function present(Manuscript $manuscript): Manuscript
    {
        return $manuscript->load(['journal', 'author', 'keywords', 'authors.user']);
    }
}
