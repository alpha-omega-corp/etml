<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Support\UnitKind;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A selection: the deck a user cuts out of a list they were given.
 *
 * It is a unit like any other — it is played by the same game, listed in the
 * same rail, renamed the same way — with two differences: it belongs to one
 * user, and it holds no words of its own. It points at the cards the chapter
 * already wrote, so a word marked « je sais » is known on both at once.
 */
class SelectionController extends Controller
{
    /**
     * Put the words ticked in a list into a selection: a new one, or one the
     * user already has.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'unit' => ['required', 'integer', 'exists:units,id'],
            'selection' => ['nullable', 'integer'],
            'name' => ['required_without:selection', 'nullable', 'string', 'min:2', 'max:120'],
            'cards' => ['required', 'array', 'min:1'],
            'cards.*' => ['integer'],
        ], [
            'unit.required' => 'Liste introuvable.',
            'name.required_without' => 'Donnez un nom à cette sélection.',
            'name.min' => 'Au moins 2 caractères.',
            'name.max' => 'Au plus 120 caractères.',
            'cards.required' => 'Choisissez au moins un mot.',
            'cards.min' => 'Choisissez au moins un mot.',
        ]);

        $userId = Auth::id();
        $source = Unit::findOrFail($data['unit']);

        abort_unless($source->isVisibleTo($userId) && ! $source->kind->isPersonal(), 404);

        // Only words that really are on the list the form was opened on: the
        // ids ride in the request, so they are checked rather than trusted.
        $cards = $source->cards()->whereIn('cards.id', $data['cards'])->pluck('cards.id');

        if ($cards->isEmpty()) {
            throw ValidationException::withMessages(['cards' => 'Choisissez au moins un mot.']);
        }

        $selection = $this->target($request, $source, $data);

        $added = $this->add($selection, $cards->all());

        return redirect()
            ->route('deck.show', [$selection->language, $selection->kind->slug(), $selection])
            ->with('success', $this->said($selection, $added, $cards->count()));
    }

    /**
     * Drop a selection. The words are the chapter's and stay where they are;
     * only the list goes.
     */
    public function destroy(Request $request, Unit $unit): RedirectResponse
    {
        abort_unless($unit->kind->isPersonal() && $unit->isOwnedBy(Auth::id()), 404);

        $name = $unit->name;
        $language = $unit->language;

        $unit->delete();

        if ($request->session()->get('unit_id') === $unit->id) {
            $request->session()->forget('unit_id');
        }

        return redirect()
            ->route('program.show', $language)
            ->with('success', "« {$name} » supprimée.");
    }

    /**
     * The selection the words go into: the one that was chosen, or a new one.
     *
     * @param  array<string, mixed>  $data
     */
    private function target(Request $request, Unit $source, array $data): Unit
    {
        $userId = Auth::id();

        if (! empty($data['selection'])) {
            $selection = Unit::find($data['selection']);

            abort_unless(
                $selection !== null
                    && $selection->kind->isPersonal()
                    && $selection->isOwnedBy($userId)
                    && $selection->language_id === $source->language_id,
                404,
            );

            return $selection;
        }

        return Unit::create([
            'language_id' => $source->language_id,
            'user_id' => $userId,
            'kind' => UnitKind::Selection,
            'name' => trim((string) $data['name']),
            'position' => Unit::nextPosition($source->language, UnitKind::Selection, $userId),
        ]);
    }

    /**
     * Add the cards the selection does not already hold, after the ones it
     * does. Returns how many were actually added, so a second pass over the
     * same words reports « déjà » rather than a lie.
     *
     * @param  array<int, int>  $cardIds
     */
    private function add(Unit $selection, array $cardIds): int
    {
        return DB::transaction(function () use ($selection, $cardIds) {
            $held = $selection->cards()->pluck('cards.id')->all();
            $new = array_values(array_diff($cardIds, $held));

            if ($new === []) {
                return 0;
            }

            $position = (int) DB::table('card_unit')->where('unit_id', $selection->id)->max('position');

            $selection->cards()->attach(array_combine(
                $new,
                array_map(fn (int $i) => ['position' => $position + $i + 1], array_keys($new)),
            ));

            return count($new);
        });
    }

    private function said(Unit $selection, int $added, int $chosen): string
    {
        if ($added === 0) {
            return "Ces mots sont déjà dans « {$selection->name} ».";
        }

        $plural = $added > 1 ? 's' : '';
        $words = "{$added} mot{$plural}";
        $skipped = $chosen - $added;

        return $skipped > 0
            ? "{$words} ajouté{$plural} à « {$selection->name} », {$skipped} déjà là."
            : "{$words} dans « {$selection->name} ».";
    }
}
