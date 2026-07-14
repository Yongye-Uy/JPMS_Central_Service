<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Manuscript;
use App\Models\Review;
use App\Models\ReviewInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** Module 7 function 5: Journal performance report. */
    public function journalPerformance(Request $request)
    {
        $journalId = $request->query('journal_id');
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Manuscript::query()->when($journalId, fn ($q) => $q->where('journal_id', $journalId));
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $total = (clone $query)->count();
        $accepted = (clone $query)->where(fn ($q) => $q->where('status', 'Accepted')->orWhere('status', 'Published'))->count();

        $avgReviewDays = ReviewInvitation::query()
            ->when($journalId, fn ($q) => $q->whereHas('manuscript', fn ($m) => $m->where('journal_id', $journalId)))
            ->join('reviews', 'reviews.invitation_id', '=', 'review_invitations.id')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (reviews.submitted_at - review_invitations.invited_at)) / 86400) as avg_days')
            ->value('avg_days');

        $publishedArticles = Article::query()
            ->whereNotNull('published_at')
            ->when($journalId, fn ($q) => $q->whereHas('issue', fn ($i) => $i->where('journal_id', $journalId)))
            ->count();

        return response()->json([
            'total_submissions' => $total,
            'accepted' => $accepted,
            'acceptance_rate' => $total > 0 ? round($accepted / $total * 100, 1) : 0,
            'avg_review_days' => $avgReviewDays !== null ? round((float) $avgReviewDays, 1) : null,
            'published_articles' => $publishedArticles,
        ]);
    }

    /** Module 7 function 6: Reviewer performance report. */
    public function reviewerPerformance(Request $request)
    {
        $reviewerId = $request->query('reviewer_id');

        $invitations = ReviewInvitation::query()->when($reviewerId, fn ($q) => $q->where('reviewer_id', $reviewerId));

        $total = (clone $invitations)->count();
        $completed = (clone $invitations)->where('status', 'Completed')->count();

        $avgTurnaroundDays = ReviewInvitation::query()
            ->when($reviewerId, fn ($q) => $q->where('reviewer_id', $reviewerId))
            ->join('reviews', 'reviews.invitation_id', '=', 'review_invitations.id')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (reviews.submitted_at - review_invitations.accepted_at)) / 86400) as avg_days')
            ->value('avg_days');

        $recommendationDistribution = Review::query()
            ->whereHas('invitation', fn ($q) => $q->when($reviewerId, fn ($q2) => $q2->where('reviewer_id', $reviewerId)))
            ->select('recommendation', DB::raw('count(*) as count'))
            ->groupBy('recommendation')
            ->pluck('count', 'recommendation');

        return response()->json([
            'total_invitations' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'avg_turnaround_days' => $avgTurnaroundDays !== null ? round((float) $avgTurnaroundDays, 1) : null,
            'recommendation_distribution' => $recommendationDistribution,
        ]);
    }
}
