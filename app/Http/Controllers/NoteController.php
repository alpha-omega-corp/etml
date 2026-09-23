<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NoteController extends Controller
{
    /**
     * A revision note inside the app's own frame: the trail back to its
     * subject above, the page itself below.
     */
    public function show(Note $note): View
    {
        return view('notes.show', ['note' => $note->load('language')]);
    }

    /**
     * The note's page as written, plus the small block that lets it take the
     * app's theme. It brings its own scripts, so it is served sandboxed — a
     * unique origin with no access to the app's session — whether it is framed
     * or opened on its own.
     */
    public function page(Note $note): Response
    {
        $html = Str::replaceFirst('</head>', view('notes.theme')->render().'</head>', $note->html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', 'sandbox allow-scripts allow-popups');
    }
}
