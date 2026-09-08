<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Card;
use App\Models\Chapter;
use App\Support\WordList;
use App\Support\WordListSamples;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ChapterController extends Controller
{
    /**
     * Open a chapter. The choice rides in the session, so it survives a reload
     * the same way the sign-in does.
     */
    public function show(Request $request, Chapter $chapter): RedirectResponse
    {
        $request->session()->put('branch_id', $chapter->branch_id);
        $request->session()->put('chapter_id', $chapter->id);

        return redirect()->route('cards');
    }

    /**
     * The creation form lives on its own page: it carries the JSON editor and
     * the prompt template, which need more room than a sidebar disclosure.
     */
    public function create(): View
    {
        return view('chapters.create', WordListSamples::all());
    }

    /**
     * Create a chapter from a pasted JSON word list. The list is the source of
     * the deck: paste it and the chapter comes out ready to quiz.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'words' => ['required', 'string', 'max:200000'],
        ], [
            'name.required' => 'Donnez un nom au chapitre.',
            'name.min' => 'Au moins 2 caractères.',
            'name.max' => 'Au plus 120 caractères.',
            'words.required' => 'Collez la liste de mots au format JSON.',
            'words.max' => 'La liste est trop longue.',
        ]);

        $rows = $this->parseOrFail($data['words'], 'words');


        // Chapters still hang off a branch in the database; the new one joins
        // whichever branch the current chapter is in.
        $current = Chapter::find($request->session()->get('chapter_id'));

        $branch = $current?->branch
            ?? Branch::pick(Branch::ordered(), $request->session()->get('branch_id'));

        abort_if($branch === null, 404);

        $chapter = DB::transaction(function () use ($branch, $data, $rows) {
            $chapter = Chapter::create([
                'branch_id' => $branch->id,
                'name' => trim($data['name']),
                'position' => (int) Chapter::where('branch_id', $branch->id)->max('position') + 1,
            ]);

            $this->insertWords($chapter, $rows);

            return $chapter;
        });

        $request->session()->put('branch_id', $branch->id);
        $request->session()->put('chapter_id', $chapter->id);

        return redirect()
            ->route('cards')
            ->with('success', count($rows).' mots importés.');
    }

    /**
     * Rename the chapter. Kept separate from `store` so the two forms on the
     * page have their own field and can never light each other's errors up.
     */
    public function update(Request $request, Chapter $chapter): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'rename' => ['required', 'string', 'min:2', 'max:120'],
        ], [
            'rename.required' => 'Donnez un nom au chapitre.',
            'rename.min' => 'Au moins 2 caractères.',
            'rename.max' => 'Au plus 120 caractères.',
        ]);

        $chapter->update(['name' => trim($data['rename'])]);

        // Renaming happens inline in the navigation, so the usual answer is the
        // new name rather than a redirect.
        if ($request->expectsJson()) {
            return response()->json(['id' => $chapter->id, 'name' => $chapter->name]);
        }

        $request->session()->put('branch_id', $chapter->branch_id);
        $request->session()->put('chapter_id', $chapter->id);

        return redirect()->route('cards')->with('success', 'Chapitre renommé.');
    }

    /**
     * Fill a chapter that was left empty. Only an empty chapter accepts an
     * import, so an existing deck can never be silently doubled.
     */
    public function import(Request $request, Chapter $chapter): RedirectResponse
    {
        if (! $chapter->isEmpty()) {
            return back()->withErrors(['import' => 'Ce chapitre contient déjà des mots.']);
        }

        $data = $request->validate([
            'import' => ['required', 'string', 'max:200000'],
        ], [
            'import.required' => 'Collez la liste de mots au format JSON.',
            'import.max' => 'La liste est trop longue.',
        ]);

        $rows = $this->parseOrFail($data['import'], 'import');

        DB::transaction(fn () => $this->insertWords($chapter, $rows));

        $request->session()->put('branch_id', $chapter->branch_id);
        $request->session()->put('chapter_id', $chapter->id);

        return redirect()
            ->route('cards')
            ->with('success', count($rows).' mots importés.');
    }

    /**
     * Delete a chapter and everything under it. Administrator only.
     */
    public function destroy(Request $request, Chapter $chapter): RedirectResponse
    {
        $name = $chapter->name;

        $chapter->delete();

        if ($request->session()->get('chapter_id') === $chapter->id) {
            $request->session()->forget('chapter_id');
        }

        return redirect()
            ->route('cards')
            ->with('success', "Chapitre « {$name} » supprimé.");
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseOrFail(string $json, string $field): array
    {
        try {
            return WordList::parse($json);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([$field => $e->getMessage()]);
        }
    }

    /**
     * @param  array<int, array<string, string|null>>  $rows
     */
    private function insertWords(Chapter $chapter, array $rows): void
    {
        $now = now();

        Card::insert(array_map(fn (array $row, int $i) => [
            'chapter_id' => $chapter->id,
            'section' => $row['section'],
            'de' => $row['de'],
            'fr' => $row['fr'],
            'example' => $row['example'],
            'position' => $i,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows, array_keys($rows)));
    }
}
