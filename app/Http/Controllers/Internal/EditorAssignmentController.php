<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EditorAssignmentController extends Controller
{
    /** Module 4 function 2: Assign editor. */
    public function store(Request $request, int $manuscriptId)
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        $data = Validator::make($request->all(), [
            'editor_id' => 'required|integer|exists:users,id',
            'role' => 'nullable|string',
        ])->validate();

        $assignment = $manuscript->editorAssignments()->create([
            'editor_id' => $data['editor_id'],
            'assigned_at' => now(),
            'role' => $data['role'] ?? 'Handling Editor',
        ]);

        return response()->json($assignment, 201);
    }
}
