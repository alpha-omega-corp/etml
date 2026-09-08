<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Card;
use App\Models\CardState;
use App\Models\Chapter;
use App\Support\WordListSamples;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CardController extends Controller
{
    public function index(Request $request): View
    {
        // Every chapter, ordered by the branch it belongs to and then by its own
        // position. With no branch navigation on screen, a per-branch list would
        // leave the other branches' chapters unreachable.
        $chapters = Chapter::withCount('cards')
            ->orderBy(Branch::select('position')->whereColumn('branches.id', 'chapters.branch_id'))
            ->orderBy('position')
            ->get();

        $chapter = $this->currentChapter($request, $chapters);
        $branch = $chapter?->branch;

        $cards = $chapter
            ? Card::where('chapter_id', $chapter->id)->orderBy('position')->get()
            : new Collection;

        $states = CardState::where('user_id', Auth::id())
            ->whereIn('card_id', $cards->pluck('id'))
            ->pluck('status', 'card_id');

        return view('cards', WordListSamples::all() + [
            'branch' => $branch,
            'chapters' => $chapters,
            'chapter' => $chapter,
            'deck' => $cards->map(fn (Card $card) => [
                'id' => $card->id,
                'sec' => $card->section,
                'de' => $card->de,
                'fr' => $card->fr,
                'ex' => $card->example,
            ])->all(),
            'states' => $states->all(),
            'isAdmin' => $request->session()->get(AdminController::SESSION_KEY) === true,
        ]);
    }

    /**
     * The chapter in the session, but only if it belongs to the branch on
     * screen; otherwise the branch's first chapter.
     *
     * @param  Collection<int, Chapter>  $chapters
     */
    private function currentChapter(Request $request, Collection $chapters): ?Chapter
    {
        $id = $request->session()->get('chapter_id');

        $chapter = $chapters->firstWhere('id', $id) ?? $chapters->first();

        if ($chapter && $chapter->id !== $id) {
            $request->session()->put('chapter_id', $chapter->id);
        }

        return $chapter;
    }

    /**
     * Mark a card as known / to review, or clear it (status = null).
     */
    public function setStatus(Request $request, Card $card): JsonResponse
    {
        $data = $request->validate([
            'status' => ['present', 'nullable', 'in:'.CardState::STATUS_KNOWN.','.CardState::STATUS_REVIEW],
        ]);

        $userId = Auth::id();

        if ($data['status'] === null) {
            CardState::where('user_id', $userId)->where('card_id', $card->id)->delete();
        } else {
            CardState::updateOrCreate(
                ['user_id' => $userId, 'card_id' => $card->id],
                ['status' => $data['status']],
            );
        }

        return response()->json(['id' => $card->id, 'status' => $data['status']]);
    }

    /**
     * Drop every mark for the signed-in user.
     */
    public function reset(): JsonResponse
    {
        CardState::where('user_id', Auth::id())->delete();

        return response()->json(['ok' => true]);
    }
}
