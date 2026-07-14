<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /** Module 4 function 5: Communicate with author (message thread tied to a manuscript). */
    public function index(int $manuscriptId)
    {
        return response()->json(
            Manuscript::findOrFail($manuscriptId)->messages()->orderBy('sent_at')->get()
        );
    }

    public function store(Request $request, int $manuscriptId)
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        $data = Validator::make($request->all(), [
            'sender_id' => 'required|integer|exists:users,id',
            'recipient_id' => 'required|integer|exists:users,id',
            'subject' => 'nullable|string',
            'body' => 'required|string',
        ])->validate();

        $message = $manuscript->messages()->create([...$data, 'sent_at' => now(), 'read_status' => false]);

        return response()->json($message, 201);
    }

    public function markRead(int $id)
    {
        $message = Message::findOrFail($id);
        $message->update(['read_status' => true]);

        return response()->json($message);
    }
}
