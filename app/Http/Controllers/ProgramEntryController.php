<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\ProgramEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * One line of a programme, added or corrected by hand.
 *
 * The whole year still arrives as a pasted JSON block; this is for the single
 * date that moves, the test that is announced late, the line that was a typo.
 * Both cases go through one endpoint: the form carries `entry_id` when it is
 * editing and leaves it empty when it is adding, so a rejected save can be
 * re-rendered exactly as it was sent.
 */
class ProgramEntryController extends Controller
{
    public function save(Request $request): RedirectResponse
    {
        $language = Language::where('slug', $request->input('language'))->firstOrFail();

        $data = $request->validate([
            'entry_id' => ['nullable', 'integer', Rule::exists('program_entries', 'id')->where('language_id', $language->id)],
            'date' => ['required', 'date_format:Y-m-d'],
            'title' => ['nullable', 'string', 'max:200'],
            'note' => ['nullable', 'string', 'max:200'],
            'units' => ['array', 'max:20'],
            'units.*' => ['integer', Rule::exists('units', 'id')->where('language_id', $language->id)],
        ], [
            'date.required' => 'Donnez une date.',
            'date.date_format' => 'La date doit être au format 2026-09-22.',
            'title.max' => 'Au plus 200 caractères.',
            'note.max' => 'Au plus 200 caractères.',
            'units.max' => '20 éléments au maximum.',
            'units.*.exists' => 'Ce chapitre ou cette page n\'appartient pas à cette langue.',
        ]);

        $entry = isset($data['entry_id'])
            ? ProgramEntry::findOrFail($data['entry_id'])
            : new ProgramEntry(['language_id' => $language->id]);

        $entry->fill([
            'date' => $data['date'],
            'title' => $this->clean($data['title'] ?? null),
            'note' => $this->clean($data['note'] ?? null),
        ])->save();

        $entry->units()->sync($data['units'] ?? []);

        return redirect()
            ->route('program.show', $language)
            ->with('success', isset($data['entry_id']) ? 'Date modifiée.' : 'Date ajoutée.');
    }

    public function destroy(ProgramEntry $entry): RedirectResponse
    {
        $language = $entry->language;
        $name = $entry->title ?? $entry->date->translatedFormat('j F Y');

        $entry->delete();

        return redirect()
            ->route('program.show', $language)
            ->with('success', "« {$name} » retiré du programme.");
    }

    /**
     * An empty field is no value at all, so the timeline falls back to its
     * default wording rather than printing a blank line.
     */
    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
