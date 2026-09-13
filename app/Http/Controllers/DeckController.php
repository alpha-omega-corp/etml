<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardState;
use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use App\Support\WordListSamples;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeckController extends Controller
{
    /**
     * One deck, played. Vocabulary chapters and verb pages both land here: the
     * card game is the same module, only the language and the wording around
     * it change.
     */
    public function show(Request $request, Language $language, string $kind, Unit $unit): View
    {
        $kind = UnitKind::fromSlug($kind);

        abort_if($kind === null, 404);
        abort_unless($unit->language_id === $language->id && $unit->kind === $kind, 404);

        $request->session()->put('language_id', $language->id);
        $request->session()->put('unit_id', $unit->id);

        $siblings = $language->unitsOfKind($kind);

        $cards = $unit->cards()->get();

        $states = CardState::where('user_id', Auth::id())
            ->whereIn('card_id', $cards->pluck('id'))
            ->pluck('status', 'card_id');

        return view('deck.show', WordListSamples::for($language) + [
            'language' => $language,
            'kind' => $kind,
            'unit' => $unit,
            'units' => $siblings,
            'known' => CardState::knownPerUnit(Auth::id(), $siblings->pluck('id')->all()),
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
