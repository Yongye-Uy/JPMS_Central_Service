<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use App\Models\ReviewInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewInvitationController extends Controller
{
    /** Reviewer dashboard / review history: list invitations, filterable by reviewer_id/manuscript_id/status. */
    public function index(Request $request)
    {
        $query = ReviewInvitation::query()->with(['manuscript.journal', 'reviewer', 'review.scores']);

        if ($reviewerId = $request->query('reviewer_id')) {
            $query->where('reviewer_id', $reviewerId);
        }
        if ($manuscriptId = $request->query('manuscript_id')) {
            $query->where('manuscript_id', $manuscriptId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderByDesc('invited_at')->paginate($request->integer('per_page', 20)));
    }

    public function show(int $id)
    {
        return response()->json(
            ReviewInvitation::with(['manuscript.journal', 'manuscript.author', 'manuscript.currentVersion.files', 'reviewer', 'review.scores'])->findOrFail($id)
        );
    }

    /** Module 3 function 2: Invite reviewer. */
    public function store(Request $request, int $manuscriptId)
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        $data = Validator::make($request->all(), [
            'reviewer_id' => 'required|integer|exists:users,id',
            'invited_by' => 'required|integer|exists:users,id',
            'deadline' => 'required|date',
        ])->validate();

        $existingActive = ReviewInvitation::where('manuscript_id', $manuscriptId)
            ->where('reviewer_id', $data['reviewer_id'])
            ->whereNotIn('status', ['Declined', 'Completed'])
            ->exists();

        if ($existingActive) {
            return response()->json(['message' => 'This reviewer already has an active invitation for this manuscript.'], 422);
        }

        $invitation = $manuscript->reviewInvitations()->create([
            'reviewer_id' => $data['reviewer_id'],
            'invited_by' => $data['invited_by'],
            'deadline' => $data['deadline'],
            'status' => 'Pending',
            'invited_at' => now(),
        ]);

        if ($manuscript->status === 'Submitted') {
            $manuscript->update(['status' => 'Under Review']);
        }

        return response()->json($invitation, 201);
    }

    /** Reviewer accepts/declines an invitation. */
    public function respond(Request $request, int $id)
    {
        $invitation = ReviewInvitation::findOrFail($id);

        if ($invitation->status !== 'Pending') {
            return response()->json(['message' => 'This invitation has already been responded to.'], 422);
        }

        $data = Validator::make($request->all(), [
            'status' => 'required|string|in:Accepted,Declined',
            'declined_reason' => 'required_if:status,Declined|nullable|string',
        ])->validate();

        $invitation->update([
            'status' => $data['status'],
            'accepted_at' => $data['status'] === 'Accepted' ? now() : null,
            'declined_reason' => $data['declined_reason'] ?? null,
        ]);

        return response()->json($invitation->fresh());
    }

    /** Module 3 function 5: Request review extension (no dedicated table, per docx). */
    public function requestExtension(Request $request, int $id)
    {
        $invitation = ReviewInvitation::findOrFail($id);

        $data = Validator::make($request->all(), [
            'requested_deadline' => 'required|date',
            'reason' => 'required|string',
        ])->validate();

        $invitation->update([
            'requested_deadline' => $data['requested_deadline'],
            'extension_reason' => $data['reason'],
            'extension_status' => 'Pending',
        ]);

        return response()->json($invitation->fresh());
    }

    public function decideExtension(Request $request, int $id)
    {
        $invitation = ReviewInvitation::findOrFail($id);

        $data = Validator::make($request->all(), [
            'approved' => 'required|boolean',
        ])->validate();

        $invitation->update(['extension_status' => $data['approved'] ? 'Approved' : 'Rejected']);

        if ($data['approved'] && $invitation->requested_deadline) {
            $invitation->update(['deadline' => $invitation->requested_deadline]);
        }

        return response()->json($invitation->fresh());
    }
}
