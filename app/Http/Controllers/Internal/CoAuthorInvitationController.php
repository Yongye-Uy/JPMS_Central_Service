<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\CoAuthorInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CoAuthorInvitationController extends Controller
{
    /** Module 2 function 4: list co-author invitations, filterable by invited_author_id/status. */
    public function index(Request $request)
    {
        $query = CoAuthorInvitation::query()->with(['manuscript.journal', 'invitingAuthor']);

        if ($invitedId = $request->query('invited_author_id')) {
            $query->where('invited_author_id', $invitedId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderByDesc('invited_at')->paginate($request->integer('per_page', 20)));
    }

    /** Module 2 function 4: accept/decline a co-author invitation. */
    public function respond(Request $request, int $id)
    {
        $invitation = CoAuthorInvitation::findOrFail($id);

        $data = Validator::make($request->all(), [
            'status' => 'required|string|in:Accepted,Declined',
            'author_order' => 'required_if:status,Accepted|integer',
            'is_corresponding' => 'sometimes|boolean',
        ])->validate();

        DB::transaction(function () use ($invitation, $data) {
            $invitation->update(['status' => $data['status'], 'responded_at' => now()]);

            if ($data['status'] === 'Accepted') {
                Author::firstOrCreate(
                    ['manuscript_id' => $invitation->manuscript_id, 'user_id' => $invitation->invited_author_id],
                    ['author_order' => $data['author_order'], 'is_corresponding' => $data['is_corresponding'] ?? false]
                );
            }
        });

        return response()->json($invitation->fresh());
    }
}
