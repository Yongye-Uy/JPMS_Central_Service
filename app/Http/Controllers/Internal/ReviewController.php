<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewFile;
use App\Models\ReviewInvitation;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function __construct(private readonly FileStorageService $files) {}

    /** Module 3 function 3 + 4: Submit review (scores + recommendation + optional annotated file). */
    public function store(Request $request, int $invitationId)
    {
        $invitation = ReviewInvitation::findOrFail($invitationId);

        if ($invitation->status === 'Completed') {
            return response()->json(['message' => 'This review has already been submitted and cannot be changed.'], 422);
        }
        if ($invitation->status !== 'Accepted') {
            return response()->json(['message' => 'You must accept the invitation before submitting a review.'], 422);
        }

        $data = Validator::make($request->all(), [
            'recommendation' => 'required|string|in:'.implode(',', Review::RECOMMENDATIONS),
            'comments_to_author' => 'required|string',
            'comments_to_editor' => 'nullable|string',
            'scores' => 'required|array',
            'scores.*.criterion' => 'required|string|in:'.implode(',', Review::CRITERIA),
            'scores.*.score' => 'required|integer|min:0|max:5',
            'annotated_file' => 'sometimes|file',
        ])->validate();

        $review = DB::transaction(function () use ($invitation, $data, $request) {
            $review = Review::updateOrCreate(
                ['invitation_id' => $invitation->id],
                [
                    'recommendation' => $data['recommendation'],
                    'comments_to_author' => $data['comments_to_author'],
                    'comments_to_editor' => $data['comments_to_editor'] ?? null,
                    'submitted_at' => now(),
                ]
            );

            $review->scores()->delete();
            foreach ($data['scores'] as $score) {
                $review->scores()->create($score);
            }

            if ($request->hasFile('annotated_file')) {
                $path = $this->files->storeReviewAnnotatedFile($review->id, $request->file('annotated_file'));
                ReviewFile::create(['review_id' => $review->id, 'file_path' => $path, 'uploaded_at' => now()]);
            }

            $invitation->update(['status' => 'Completed']);

            $manuscript = $invitation->manuscript;
            $hasOutstanding = $manuscript->reviewInvitations()
                ->whereIn('status', ['Pending', 'Accepted', 'In Progress', 'Overdue'])
                ->exists();

            if ($manuscript->status === 'Under Review' && ! $hasOutstanding) {
                $manuscript->update(['status' => 'Ready for Decision']);
            }

            return $review;
        });

        return response()->json($review->load(['scores', 'files']), 201);
    }

    public function show(int $id)
    {
        return response()->json(Review::with(['scores', 'files', 'invitation.reviewer', 'invitation.manuscript.authors'])->findOrFail($id));
    }

    /** Streams a review's annotated PDF. */
    public function downloadFile(int $reviewId, int $fileId)
    {
        $file = ReviewFile::findOrFail($fileId);

        if ($file->review_id !== $reviewId || ! $this->files->exists($file->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return $this->files->stream($file->file_path, 'annotated.pdf');
    }
}
