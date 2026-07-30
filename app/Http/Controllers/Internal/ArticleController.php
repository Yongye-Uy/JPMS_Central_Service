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
        $baseQuery = Article::query();

        if (! $request->boolean('include_unpublished')) {
            $baseQuery->whereNotNull('published_at');
        }
        if ($journalId = $request->query('journal_id')) {
            $baseQuery->whereHas('issue', fn ($q) => $q->where('journal_id', $journalId));
        }
        if ($issueId = $request->query('issue_id')) {
            $baseQuery->where('issue_id', $issueId);
        }
        if ($year = $request->query('year')) {
            $baseQuery->whereHas('issue', fn ($q) => $q->where('year', $year));
        }
        if ($search = $request->query('q')) {
            $field = $request->query('field');
            $baseQuery->whereHas('manuscript', function ($q) use ($search, $field) {
                if ($field === 'title') {
                    $q->where('title', 'ilike', "%{$search}%");
                } elseif ($field === 'author') {
                    $q->whereHas('authors.user', fn ($u) => $u->where('full_name', 'ilike', "%{$search}%"));
                } elseif ($field === 'keyword') {
                    $q->whereHas('keywords', fn ($k) => $k->where('keyword_text', 'ilike', "%{$search}%"));
                } elseif ($field === 'abstract') {
                    $q->where('abstract', 'ilike', "%{$search}%");
                } else {
                    $q->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$search])
                      ->orWhereHas('authors.user', fn ($u) => $u->where('full_name', 'ilike', "%{$search}%"))
                      ->orWhereHas('keywords', fn ($k) => $k->where('keyword_text', 'ilike', "%{$search}%"));
                }
            });
        }

        // 1. Get the total count quickly WITHOUT evaluating complex withSum aggregations.
        // We cache this count for 10 minutes because Postgres sequential scans on 500k rows
        // will choke the DB if 20 users request it concurrently.
        $cacheQuery = $request->except(['page', 'per_page']);
        $cacheKey = 'articles_count_' . md5(json_encode($cacheQuery));
        $total = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($baseQuery) {
            return $baseQuery->count();
        });

        // 2. Fetch only the IDs for the current page. 
        // This forces Postgres to use the published_at index and prevents it from 
        // accidentally executing the withSum subqueries on the entire 500,000 row table.
        $perPage = $request->integer('per_page', 20);
        $page = $request->integer('page', 1);
        
        $itemIds = $baseQuery->clone()
            ->orderByDesc('published_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->pluck('id');

        // 3. Fetch the full items with expensive aggregations using ONLY those IDs
        if ($itemIds->isEmpty()) {
            $items = collect();
        } else {
            $items = Article::query()
                ->whereIn('id', $itemIds)
                ->with(['manuscript.author', 'manuscript.keywords', 'issue.journal'])
                ->withSum('metrics as views', 'views')
                ->withSum('metrics as downloads', 'downloads')
                ->withSum('metrics as citations_count', 'citations_count')
                ->get()
                // Re-sort the collection in memory to match the original ID order
                ->sortBy(fn ($model) => $itemIds->search($model->id))
                ->values();
        }

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json($paginator);
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
