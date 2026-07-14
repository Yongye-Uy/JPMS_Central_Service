<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $query = Journal::query()->with('editorInChief');

        if (! $request->boolean('include_archived')) {
            $query->where('is_archived', false);
        }

        return response()->json($query->orderBy('title')->paginate($request->integer('per_page', 20)));
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'title' => 'required|string',
            'issn' => 'nullable|string|unique:journals,issn',
            'scope_description' => 'nullable|string',
            'editor_in_chief_id' => 'nullable|integer|exists:users,id',
        ])->validate();

        if (empty($data['issn'])) {
            $data['issn'] = $this->generateFakeIssn();
        }

        return response()->json(Journal::create($data), 201);
    }

    /** Demo-only fake ISSN (XXXX-XXXX, matching the real format) for journals created without one. */
    private function generateFakeIssn(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = sprintf('%04d-%04d', random_int(0, 9999), random_int(0, 9999));
            if (! Journal::where('issn', $candidate)->exists()) {
                return $candidate;
            }
        }

        return sprintf('%04d-%04d', random_int(0, 9999), random_int(0, 9999)).'-'.random_int(0, 9);
    }

    public function show(int $id)
    {
        return response()->json(Journal::with(['editorInChief', 'issues'])->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $journal = Journal::findOrFail($id);

        $data = Validator::make($request->all(), [
            'title' => 'sometimes|string',
            'issn' => 'sometimes|nullable|string|unique:journals,issn,'.$id,
            'scope_description' => 'nullable|string',
            'editor_in_chief_id' => 'nullable|integer|exists:users,id',
        ])->validate();

        $journal->update($data);

        return response()->json($journal->fresh());
    }

    public function archive(int $id)
    {
        $journal = Journal::findOrFail($id);
        $journal->update(['is_archived' => true]);

        return response()->json($journal);
    }

    public function restore(int $id)
    {
        $journal = Journal::findOrFail($id);
        $journal->update(['is_archived' => false]);

        return response()->json($journal);
    }
}
