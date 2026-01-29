<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteShareController extends Controller
{
    /**
     * Display the note content as a public flat page (no auth).
     */
    public function show(Request $request, string $token): View|\Illuminate\Http\Response
    {
        if (empty($token)) {
            abort(404);
        }

        $note = Note::query()->where('share_token', $token)->first();

        if (! $note) {
            abort(404);
        }

        return view('notes.public-show', [
            'note' => $note,
        ]);
    }
}
