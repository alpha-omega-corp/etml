<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use App\Support\WordList;
use App\Support\WordListSamples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Chapters and verb pages are the same thing — a unit — and are created,
 * renamed, filled and deleted the same way.
 */
class UnitController extends Controller
{
    /**
     * The creation form lives on its own page: it carries the JSON editor and
     * the prompt template, which need more room than a sidebar disclosure.
     */
    public function create(Request $request): View
    {
        [$language, $kind] = $this->target($request);

        return view('units.create', WordListSamples::for($language) + [
            'language' => $language,
            'kind' => $kind,
        ]);
    }

    /**
     * Create a unit from a pasted JSON word list. The list is the source of
     * the deck: paste it and the unit comes out ready to quiz.
     */
    public function store(Request $request): RedirectResponse
    {
        [$language, $kind] = $this->target($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'words' => ['required', 'string', 'max:200000'],
        ], [
            'name.required' => 'Donnez un nom à '.$kind->thisOne().'.',
            'name.min' => 'Au moins 2 caractères.',
            'name.max' => 'Au plus 120 caractères.',
            'words.required' => 'Collez la liste de mots au format JSON.',
            'words.max' => 'La liste est trop longue.',
        ]);

        $rows = $this->parseOrFail($data['words'], $language, 'words');

        $unit = DB::transaction(function () use ($language, $kind, $data, $rows) {
            $unit = Unit::create([
                'language_id' => $language->id,
                'kind' => $kind,
                'name' => trim($data['name']),
                'position' => Unit::nextPosition($language, $kind),
            ]);

            $this->insertWords($unit, $rows);

            return $unit;
        });

        return $this->toDeck($unit)->with('success', count($rows).' mots importés.');
    }

    /**
     * Rename the unit. Renaming happens inline in the navigation, so the usual
     * answer is the new name rather than a redirect.
     */
    public function update(Request $request, Unit $unit): RedirectResponse|JsonResponse
    {
        // A selection is renamed by the one person it belongs to.
        abort_unless($unit->isVisibleTo(Auth::id()), 404);

        $data = $request->validate([
            'rename' => ['required', 'string', 'min:2', 'max:120'],
        ], [
            'rename.required' => 'Donnez un nom à '.$unit->kind->thisOne().'.',
            'rename.min' => 'Au moins 2 caractères.',
            'rename.max' => 'Au plus 120 caractères.',
        ]);

        $unit->update(['name' => trim($data['rename'])]);

        if ($request->expectsJson()) {
            return response()->json(['id' => $unit->id, 'name' => $unit->name]);
        }

        return $this->toDeck($unit)->with('success', 'Nom enregistré.');
    }

    /**
     * Fill a unit that was left empty — the state a unit named by a programme
     * starts in. Only an empty unit accepts an import, so an existing deck can
     * never be silently doubled.
     */
    public function import(Request $request, Unit $unit): RedirectResponse
    {
        abort_if($unit->kind->isPersonal(), 404);

        if (! $unit->isEmpty()) {
            return back()->withErrors(['import' => ucfirst($unit->kind->thisOne()).' contient déjà des mots.']);
        }

        $data = $request->validate([
            'import' => ['required', 'string', 'max:200000'],
        ], [
            'import.required' => 'Collez la liste de mots au format JSON.',
            'import.max' => 'La liste est trop longue.',
        ]);

        $rows = $this->parseOrFail($data['import'], $unit->language, 'import');

        DB::transaction(fn () => $this->insertWords($unit, $rows));

        return $this->toDeck($unit)->with('success', count($rows).' mots importés.');
    }

    /**
     * Delete a unit and everything under it. Administrator only.
     */
    public function destroy(Request $request, Unit $unit): RedirectResponse
    {
        // A selection is nobody's to delete but its owner's, who has their own
        // button for it.
        abort_if($unit->kind->isPersonal(), 404);

        $name = $unit->name;
        $language = $unit->language;

        $unit->delete();

        if ($request->session()->get('unit_id') === $unit->id) {
            $request->session()->forget('unit_id');
        }

        return redirect()
            ->route('program.show', $language)
            ->with('success', "« {$name} » supprimé.");
    }

    /**
     * Which language and which kind the form is working on. Both ride in the
     * query string so the page can be linked to from either list.
     *
     * @return array{Language, UnitKind}
     */
    private function target(Request $request): array
    {
        $language = Language::where('slug', $request->query('language', $request->input('language')))->first()
            ?? Language::find($request->session()->get('language_id'));

        abort_if($language === null, 404);

        $kind = UnitKind::fromSlug((string) $request->query('kind', $request->input('kind', 'vocabulaire')));

        // A selection is cut out of a list that already exists, never pasted
        // in, so this form does not make one.
        abort_if($kind === null || $kind->isPersonal(), 404);

        return [$language, $kind];
    }

    private function toDeck(Unit $unit): RedirectResponse
    {
        return redirect()->route('deck.show', [$unit->language, $unit->kind->slug(), $unit]);
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseOrFail(string $json, Language $language, string $field): array
    {
        try {
            return WordList::parse($json, $language->code);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([$field => $e->getMessage()]);
        }
    }

    /**
     * @param  array<int, array<string, string|null>>  $rows
     */
    private function insertWords(Unit $unit, array $rows): void
    {
        $now = now();

        Card::insert(array_map(fn (array $row, int $i) => [
            'unit_id' => $unit->id,
            'section' => $row['section'],
            'term' => $row['term'],
            'translation' => $row['translation'],
            'example' => $row['example'],
            'position' => $i,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows, array_keys($rows)));

        // A bulk insert fires no events: the unit's own list is built here.
        $unit->relistOwnCards();
    }
}
