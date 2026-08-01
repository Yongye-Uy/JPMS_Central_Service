<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Manuscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IssueController extends Controller
{
    /** Module 5 function 1 + 6: Search issues (public browse filters by status=Published). */
    public function index(Request $request)
    {
        $query = Issue::query()->with('journal');

        if ($journalId = $request->query('journal_id')) {
            $query->where('journal_id', $journalId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderByDesc('year')->orderByDesc('number')->paginate($request->integer('per_page', 20)));
    }

    /** Module 5 function 2: Create journal issue. */
    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'journal_id' => 'required|integer|exists:journals,id',
            'volume' => 'required|integer',
            'number' => 'required|integer',
            'year' => 'required|integer',
            'publication_date' => 'nullable|date',
        ])->validate();

        $issue = Issue::create([...$data, 'status' => 'Draft']);

        return response()->json($issue, 201);
    }

    public function show(int $id, Request $request)
    {
        $query = Issue::with('journal');
        
        if (! $request->boolean('without_articles')) {
            $query->with(['articles.manuscript.author', 'articles.manuscript.keywords']);
        }
        
        return response()->json($query->findOrFail($id));
    }

    /** Module 5 function 5: Add article to issue (generates DOI: dd.mm.yyyy/JPMS-{article_id} once persisted). */
    public function addArticle(Request $request, int $issueId)
    {
        $issue = Issue::findOrFail($issueId);

        $data = Validator::make($request->all(), [
            'manuscript_id' => 'required|integer|exists:manuscripts,id',
            'page_start' => 'nullable|integer',
            'page_end' => 'nullable|integer',
        ])->validate();

        $manuscript = Manuscript::findOrFail($data['manuscript_id']);

        // Module 5 function 7 ("Generate Article PDF") is satisfied here by
        // reusing the accepted manuscript's current version file as the
        // article's public PDF — there's no separate branding/pagination
        // pipeline in this system, so the source file IS the article PDF.
        $article = Article::create([
            'manuscript_id' => $manuscript->id,
            'issue_id' => $issue->id,
            'source_version_id' => $manuscript->current_version_id,
            'pdf_path' => $manuscript->currentVersion?->file_path,
            'page_start' => $data['page_start'] ?? null,
            'page_end' => $data['page_end'] ?? null,
        ]);

        $article->update(['doi' => now()->format('d.m.Y')."/JPMS-{$article->id}"]);

        return response()->json($article->load('manuscript'), 201);
    }

    /** Module 5 function 6: Publish issue. */
    public function publish(int $id)
    {
        $issue = Issue::with('articles')->findOrFail($id);

        if ($issue->articles->isEmpty()) {
            return response()->json(['message' => 'Cannot publish an issue with no articles.'], 422);
        }

        $issue->update(['status' => 'Published']);
        $issue->articles()->whereNull('published_at')->update(['published_at' => now()]);
        Manuscript::whereIn('id', $issue->articles->pluck('manuscript_id'))->update(['status' => 'Published']);

        return response()->json(['message' => 'Published successfully.']);
    }
}
