<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleMetric;
use App\Services\Cache\CacheAsideService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function __construct(
        private readonly FileStorageService $files,
        private readonly CacheAsideService $cache,
    ) {}

    /** Module 6 function 1: Public search — title/author/keyword/abstract/journal, published only unless include_unpublished. */
    public function index(Request $request)
    {
        $query = Article::query()
            ->with(['manuscript.author', 'manuscript.keywords', 'issue.journal'])
            ->withSum('metrics as views', 'views')
            ->withSum('metrics as downloads', 'downloads')
            ->withSum('metrics as citations_count', 'citations_count');

        if (! $request->boolean('include_unpublished')) {
            $query->whereNotNull('published_at');
        }
        if ($journalId = $request->query('journal_id')) {
            $query->whereHas('issue', fn ($q) => $q->where('journal_id', $journalId));
        }
        if ($issueId = $request->query('issue_id')) {
            $query->where('issue_id', $issueId);
        }
        if ($year = $request->query('year')) {
            $query->whereHas('issue', fn ($q) => $q->where('year', $year));
        }
        if ($search = $request->query('q')) {
            $query->whereHas('manuscript', function ($q) use ($search) {
                $q->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$search])
                  ->orWhereHas('authors.user', fn ($u) => $u->where('full_name', 'ilike', "%{$search}%"))
                  ->orWhereHas('keywords', fn ($k) => $k->where('keyword_text', 'ilike', "%{$search}%"));
            });
        }

        return response()->json($query->orderByDesc('published_at')->paginate($request->integer('per_page', 20)));
    }

    public function show(int $id)
    {
        // Cache the plain array form, not the Eloquent model — see the same
        // fix (and why) in Internal/ManuscriptController::show().
        $article = $this->cache->remember(
            CacheAsideService::entityKey('article', $id),
            fn () => Article::with(['manuscript.author', 'manuscript.authors.user', 'manuscript.keywords', 'issue.journal'])
                ->withSum('metrics as views', 'views')
                ->withSum('metrics as downloads', 'downloads')
                ->withSum('metrics as citations_count', 'citations_count')
                ->findOrFail($id)
                ->toArray()
        );

        return response()->json($article);
    }

    /** Module 5 function 5 (edit pages) / remove-from-issue support. */
    public function update(Request $request, int $id)
    {
        $article = Article::findOrFail($id);

        $data = Validator::make($request->all(), [
            'page_start' => 'nullable|integer',
            'page_end' => 'nullable|integer',
        ])->validate();

        $article->update($data);

        $this->cache->forget(CacheAsideService::entityKey('article', $article->id));

        return response()->json($article->fresh());
    }

    public function destroy(int $id)
    {
        $article = Article::with('issue')->findOrFail($id);

        if ($article->issue && $article->issue->status !== 'Draft') {
            return response()->json(['message' => 'Cannot remove an article from a published issue.'], 422);
        }

        $article->delete();

        $this->cache->forget(CacheAsideService::entityKey('article', $id));

        return response()->json(null, 204);
    }

    /** Module 6 function 4: Download article PDF (streamed, never buffered). */
    public function download(Request $request, int $id)
    {
        $article = Article::findOrFail($id);

        if (! $article->pdf_path || ! $this->files->exists($article->pdf_path)) {
            return response()->json(['message' => 'PDF not available.'], 404);
        }

        $disposition = $request->query('disposition') === 'attachment' ? 'attachment' : 'inline';

        return $this->files->stream($article->pdf_path, "article-{$article->id}.pdf", null, $disposition);
    }

    /** Module 7 function 2: Track article metrics (view|download|citation event). */
    public function trackMetric(Request $request, int $id)
    {
        $article = Article::findOrFail($id);

        $data = Validator::make($request->all(), [
            'event' => 'required|string|in:view,download,citation',
        ])->validate();

        $metric = ArticleMetric::firstOrCreate(
            ['article_id' => $article->id, 'metric_date' => now()->toDateString()],
            ['views' => 0, 'downloads' => 0, 'citations_count' => 0]
        );

        $column = ['view' => 'views', 'download' => 'downloads', 'citation' => 'citations_count'][$data['event']];
        $metric->increment($column);

        $this->cache->forget(CacheAsideService::entityKey('article', $article->id));

        return response()->json($metric->fresh());
    }

    /**
     * Read-only fetch of today's metric row, no increment — lets a client
     * that tracked an event out-of-band (e.g. a PDF download opened in a
     * separate tab) refresh its displayed counts without re-tracking a view.
     */
    public function todayMetrics(int $id)
    {
        $metric = ArticleMetric::firstOrCreate(
            ['article_id' => $id, 'metric_date' => now()->toDateString()],
            ['views' => 0, 'downloads' => 0, 'citations_count' => 0]
        );

        return response()->json($metric);
    }
}
