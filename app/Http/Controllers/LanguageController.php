<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardState;
use App\Models\Language;
use App\Models\ProgramEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LanguageController extends Controller
{
    /**
     * The front door: what would you like to learn? Each language shows what
     * it holds, how far the signed-in user has got, and the next date on its
     * programme.
     */
    public function index(Request $request): View
    {
        $languages = Language::withCount('units')->orderBy('position')->get();

        $totals = Card::query()
            ->join('units', 'units.id', '=', 'cards.unit_id')
            ->groupBy('units.language_id')
            ->selectRaw('units.language_id as language_id, count(*) as total')
            ->pluck('total', 'language_id');

        $known = CardState::knownPerLanguage(Auth::id());

        $next = ProgramEntry::query()
            ->whereDate('date', '>=', Carbon::today())
            ->orderBy('date')
            ->get()
            ->keyBy('language_id');

        return view('languages.index', [
            'languages' => $languages->map(fn (Language $language) => [
                'language' => $language,
                'units' => $language->units_count,
                'cards' => (int) ($totals[$language->id] ?? 0),
                'known' => $known[$language->id] ?? 0,
                'next' => $next->get($language->id),
            ]),
            'current' => $request->session()->get('language_id'),
        ]);
    }
}
