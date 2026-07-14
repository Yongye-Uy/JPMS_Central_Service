<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\EditorialDecision;
use App\Models\Manuscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EditorialDecisionController extends Controller
{
    private const STATUS_BY_DECISION = [
        'Accept' => 'Accepted',
        'Return to Edit' => 'Revision Required',
        'Minor Revision' => 'Revision Required',
        'Major Revision' => 'Revision Required',
        'Reject' => 'Rejected',
        'Desk Reject' => 'Rejected',
    ];

    /**
     * Module 4 function 3 (Editorial Screening / desk-reject) and function 4
     * (Make Editorial Decision) both land here — same underlying operation:
     * record a decision and transition the manuscript's status.
     */
    public function store(Request $request, int $manuscriptId)
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        if (! in_array($manuscript->status, Manuscript::DECIDABLE_STATUSES, true)) {
            return response()->json(['message' => 'This manuscript is not awaiting an editorial decision.'], 422);
        }

        $data = Validator::make($request->all(), [
            'editor_id' => 'required|integer|exists:users,id',
            'decision' => 'required|string|in:'.implode(',', EditorialDecision::DECISIONS),
            'decision_letter' => 'nullable|string',
        ])->validate();

        $decision = DB::transaction(function () use ($manuscript, $data) {
            $decision = $manuscript->editorialDecisions()->create([
                'editor_id' => $data['editor_id'],
                'decision' => $data['decision'],
                'decision_letter' => $data['decision_letter'] ?? null,
                'decided_at' => now(),
            ]);

            $manuscript->update(['status' => self::STATUS_BY_DECISION[$data['decision']]]);

            return $decision;
        });

        return response()->json($decision, 201);
    }
}
