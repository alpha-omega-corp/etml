<?php

namespace App\Http\Controllers;

use App\Models\CardState;
use App\Models\Language;
use App\Models\ProgramEntry;
use App\Support\ProgramImporter;
use App\Support\ProgramList;
use App\Support\UnitKind;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ProgramController extends Controller
{
    /**
     * A language's home page: the dated programme first, then everything it
     * contains — the vocabulary chapters, the verb pages and the signed-in
     * user's own selections, each a deck.
     */
    public function show(Request $request, Language $language): View
    {
        $request->session()->put('language_id', $language->id);

        $userId = auth()->id();

        $vocabulary = $language->unitsOfKind(UnitKind::Vocabulary);
        $verbs = $language->unitsOfKind(UnitKind::Verbs);
        $selections = $language->unitsOfKind(UnitKind::Selection, $userId);

        $units = $vocabulary->merge($verbs)->merge($selections);

        return view('program.show', [
            'language' => $language,
            'entries' => $language->programEntries()->with('units')->get(),
            'vocabulary' => $vocabulary,
            'verbs' => $verbs,
            'selections' => $selections,
            'known' => CardState::knownPerUnit($userId, $units->pluck('id')->all()),
        ]);
    }

    /**
     * The programme is pasted as JSON, the same way word lists are. The form
     * opens on the programme already in place, so an edit is a paste away.
     */
    public function edit(Request $request, Language $language): View
    {
        return view('program.edit', [
            'language' => $language,
            'current' => self::toJson($language->programEntries()->with('units')->get()),
        ]);
    }

    /**
     * Replace the language's programme. Units are matched by name and created
     * empty when they are new, so the programme can be pasted before the word
     * lists exist.
     */
    public function store(Request $request, Language $language): RedirectResponse
    {
        $data = $request->validate([
            'program' => ['required', 'string', 'max:200000'],
        ], [
            'program.required' => 'Collez le programme au format JSON.',
            'program.max' => 'Le programme est trop long.',
        ]);

        try {
            $rows = ProgramList::parse($data['program']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['program' => $e->getMessage()]);
        }

        ProgramImporter::apply($language, $rows);

        return redirect()
            ->route('program.show', $language)
            ->with('success', count($rows).' dates enregistrées.');
    }

    /**
     * Drop the programme, leaving the chapters and pages alone.
     */
    public function destroy(Language $language): RedirectResponse
    {
        $language->programEntries()->delete();

        return redirect()
            ->route('program.show', $language)
            ->with('success', 'Programme effacé.');
    }

    /**
     * The programme rendered back into the shape it is pasted in, so the edit
     * form can show what is already there.
     *
     * @param  Collection<int, ProgramEntry>  $entries
     */
    private static function toJson(Collection $entries): string
    {
        if ($entries->isEmpty()) {
            return '';
        }

        $rows = $entries->map(function (ProgramEntry $entry) {
            $row = ['date' => $entry->date->toDateString()];

            if ($entry->title !== null) {
                $row['test'] = $entry->title;
            }

            foreach (UnitKind::shared() as $kind) {
                $names = $entry->units->where('kind', $kind)->pluck('name')->values();

                if ($names->isNotEmpty()) {
                    $row[$kind->programKey()] = $names->all();
                }
            }

            if ($entry->note !== null) {
                $row['note'] = $entry->note;
            }

            return $row;
        });

        return json_encode($rows->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
