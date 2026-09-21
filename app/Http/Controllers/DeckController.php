<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardState;
use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use App\Support\WordListSamples;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeckController extends Controller
{
    /**
     * One deck, played. Chapters, verb pages and a user's own selections all
     * land here: the card game is the same module, only the language and the
     * wording around it change.
     */
    public function show(Request $request, Language $language, string $kind, Unit $unit): View
    {
        $kind = UnitKind::fromSlug($kind);
        $userId = Auth::id();

        abort_if($kind === null, 404);
        abort_unless($unit->language_id === $language->id && $unit->kind === $kind, 404);

        // Someone else's selection is not theirs to open, and not theirs to
        // know about either: it is missing, not forbidden.
        abort_unless($unit->isVisibleTo($userId), 404);

        $request->session()->put('language_id', $language->id);
        $request->session()->put('unit_id', $unit->id);

        $siblings = $language->unitsOfKind($kind, $userId);

        $cards = $unit->cards()->get();

        $states = CardState::where('user_id', $userId)
            ->whereIn('card_id', $cards->pluck('id'))
            ->pluck('status', 'card_id');

        return view('deck.show', WordListSamples::for($language) + [
            'language' => $language,
            'kind' => $kind,
            'unit' => $unit,
            'units' => $siblings,
            // The lists this deck's words can be added to, for the picker.
            'selections' => $kind->isPersonal()
                ? new Collection
                : $language->unitsOfKind(UnitKind::Selection, $userId),
            'known' => CardState::knownPerUnit($userId, $siblings->pluck('id')->all()),
            'deck' => $cards->map(fn (Card $card) => [
                'id' => $card->id,
                'sec' => $card->section,
                'term' => $card->term,
                'translation' => $card->translation,
                'ex' => $card->example,
            ])->all(),
            'states' => $states->all(),
        ]);
    }
}
