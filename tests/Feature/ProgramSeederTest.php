<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\ProgramEntry;
use App\Models\Unit;
use App\Support\UnitKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The 2026-2027 programmes, as printed on the class handouts.
 *
 * German keeps only the dates that carry something to learn — the holidays,
 * the other subjects' exams and the school's own dates are not part of it.
 * English is the handout's red column: its tests and their corrections, with
 * no chapter to open behind them yet.
 */
class ProgramSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\ProgramSeeder::class);
        $this->post('/login', ['username' => 'anna']);
    }

    private function german(): Language
    {
        return Language::where('slug', 'allemand')->firstOrFail();
    }

    private function english(): Language
    {
        return Language::where('slug', 'anglais')->firstOrFail();
    }

    public function test_it_loads_every_date_that_carries_something_to_learn(): void
    {
        $entries = $this->german()->programEntries()->get();

        $this->assertSame(12, $entries->count());
        $this->assertSame('2026-09-07', $entries->first()->date->toDateString());
        $this->assertSame('2027-05-10', $entries->last()->date->toDateString());
    }

    public function test_every_date_points_at_a_chapter_or_a_page(): void
    {
        foreach ($this->german()->programEntries()->with('units')->get() as $entry) {
            $this->assertNotEmpty(
                $entry->units,
                "« {$entry->title} » du {$entry->date->toDateString()} ne mène nulle part.",
            );
        }
    }

    public function test_the_chapters_are_numbered_and_the_verb_pages_named(): void
    {
        $chapters = $this->german()->unitsOfKind(UnitKind::Vocabulary)->pluck('name');
        $pages = $this->german()->unitsOfKind(UnitKind::Verbs)->pluck('name');

        $this->assertSame(['Verben 1', 'Verben 2'], $pages->all());

        // Ten chapters, numbered in the order their weeks fall, and the two
        // shipped word lists sit in the first two rather than beside them.
        $this->assertSame(
            ['Voc 1', 'Voc 2', 'Voc 3', 'Voc 4', 'Voc 5', 'Voc 6', 'Voc 7', 'Voc 8', 'Voc 9', 'Voc 10'],
            $chapters->all(),
        );

        $units = $this->german()->unitsOfKind(UnitKind::Vocabulary)->keyBy('name');
        $this->assertSame(146, $units['Voc 1']->cards_count);
        $this->assertSame(179, $units['Voc 2']->cards_count);

        $pages = $this->german()->unitsOfKind(UnitKind::Verbs)->keyBy('name');
        $this->assertSame(61, $pages['Verben 1']->cards_count);
        $this->assertSame(61, $pages['Verben 2']->cards_count);
    }

    public function test_english_gets_the_tests_from_its_own_handout(): void
    {
        $entries = $this->english()->programEntries()->get();

        $this->assertSame(5, $entries->count());
        $this->assertSame('2026-09-23', $entries->first()->date->toDateString());
        $this->assertSame('TEST 3 — Headway', $entries->last()->title);

        // Nothing to revise behind them: English has no chapters yet.
        foreach ($entries as $entry) {
            $this->assertEmpty($entry->units);
        }
    }

    public function test_the_two_programmes_do_not_bleed_into_each_other(): void
    {
        $this->get('/anglais')
            ->assertOk()
            ->assertSee('TEST 1 — Play')
            ->assertDontSee('Voc 1')
            ->assertDontSee('Verben 1');

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('Voc 1')
            ->assertDontSee('TEST 1 — Play');
    }

    public function test_a_chapter_keeps_the_range_it_covers_as_the_date_note(): void
    {
        $entry = ProgramEntry::where('date', '2026-09-28')->firstOrFail();

        $this->assertSame('Chapitre 2.2 → 3.1', $entry->note);
        $this->assertSame(['Voc 2'], $entry->units->pluck('name')->all());
    }

    public function test_a_dated_line_points_at_what_it_covers(): void
    {
        $entry = ProgramEntry::where('date', '2026-10-05')->firstOrFail();

        $this->assertSame('Verbes', $entry->title);
        $this->assertSame(['Verben 1'], $entry->units->pluck('name')->all());
    }

    public function test_the_holidays_and_the_other_subjects_are_left_out(): void
    {
        $titles = $this->german()->programEntries()->pluck('title');

        foreach (['Herbstferien', 'Weihnachtsferien', 'Sportferien', 'Osterferien', 'Portes ouvertes', 'Examen Maths Fond', 'Rendu du TIP'] as $title) {
            $this->assertNotContains($title, $titles->all());
        }

        $this->assertSame(['Verbes', 'Vocabulaire'], $titles->unique()->sort()->values()->all());
    }

    public function test_running_it_twice_does_not_double_anything(): void
    {
        $units = Unit::count();

        $this->seed(\Database\Seeders\ProgramSeeder::class);

        // Both programmes: 12 German dates and 5 English ones.
        $this->assertSame(17, ProgramEntry::count());
        $this->assertSame(12, $this->german()->programEntries()->count());
        $this->assertSame(5, $this->english()->programEntries()->count());
        $this->assertSame($units, Unit::count());
    }

    public function test_the_language_page_shows_the_programme(): void
    {
        $this->get('/allemand')
            ->assertOk()
            ->assertSee('Voc 1')
            ->assertSee('Voc 10')
            ->assertSee('Verben 1')
            ->assertSee('Verben 2')
            // The range each date covers rides along as its note, named after
            // the chapters of the book it comes from.
            ->assertSee('Chapitre 1.1 → 2.1')
            ->assertSee('Chapitre 13.3 → 15.2')
            ->assertDontSee('Aucune date')
            ->assertDontSee('Herbstferien');
    }

    public function test_a_chapter_the_programme_named_opens_ready_to_be_filled(): void
    {
        $unit = Unit::where('name', 'Voc 3')->firstOrFail();

        $this->get(route('deck.show', [$this->german(), 'vocabulaire', $unit], absolute: false))
            ->assertOk()
            ->assertSee('Ce chapitre est vide.')
            ->assertSee('Créer les cartes');
    }
}
