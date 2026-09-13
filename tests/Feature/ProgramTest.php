<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\ProgramEntry;
use App\Models\Unit;
use App\Support\UnitKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A language's programme: the dated tests and what they cover.
 */
class ProgramTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['admin.password' => 'ouvre-toi']);
        $this->post('/login', ['username' => 'anna']);
    }

    private function beAdmin(): void
    {
        $this->post('/admin', ['password' => 'ouvre-toi']);
    }

    private function german(): Language
    {
        return Language::where('slug', 'allemand')->firstOrFail();
    }

    private function program(array $rows): string
    {
        return json_encode($rows, JSON_UNESCAPED_UNICODE);
    }

    // --- the page --------------------------------------------------------------

    public function test_the_head_is_a_trail_from_home_and_a_pill_for_the_language(): void
    {
        $html = $this->get('/allemand')->assertOk()->getContent();

        // Home first, the language last — and the last crumb IS the heading,
        // so the page keeps its <h1> without a title repeating it above.
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringContainsString('href="'.route('languages.index').'" class="breadcrumbs__link">Accueil</a>', $html);
        $this->assertMatchesRegularExpression('~<h1 class="breadcrumbs__current"[^>]*aria-current="page"[^>]*>\s*Allemand\s*</h1>~', $html);
        $this->assertStringNotContainsString('program__title', $html);

        // The pill is the language's own name for itself, not the French one.
        $this->assertStringContainsString('badge badge--accent', $html);
        $this->assertStringContainsString('Deutsch', $html);

        // The trail replaces the back link that stood above the title.
        $this->assertStringNotContainsString('Toutes les langues', $html);
    }

    public function test_a_language_page_lists_its_units_by_kind(): void
    {
        Unit::create([
            'language_id' => $this->german()->id,
            'kind' => UnitKind::Verbs,
            'name' => 'Verbes forts, p. 12',
            'position' => Unit::nextPosition($this->german(), UnitKind::Verbs),
        ]);

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('Allemand')
            ->assertSee('Programme')
            ->assertSee('Vocabulaire')
            ->assertSee('Verbes')
            ->assertSee('Voc 1')
            ->assertSee('Verbes forts, p. 12')
            ->assertSee('Aucune date');
    }

    public function test_opening_a_language_remembers_it(): void
    {
        $this->get('/allemand')->assertOk();

        $this->assertSame($this->german()->id, session('language_id'));
    }

    public function test_the_page_shows_the_dates_and_links_to_what_they_cover(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->addWeek()->toDateString(), 'test' => 'Test 1', 'chapters' => ['Voc 1']],
        ])])->assertSessionHas('success');

        $unit = $this->german()->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('Test 1')
            ->assertSee('dans 7 j')
            ->assertSee(route('deck.show', [$this->german(), 'vocabulaire', $unit]), false);
    }

    public function test_a_date_that_has_passed_is_marked_as_such(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->subWeek()->toDateString(), 'test' => 'Test passé'],
            ['date' => Carbon::today()->toDateString(), 'test' => 'Test du jour'],
        ])]);

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('is-past', false)
            ->assertSee('is-today', false)
            ->assertSee("Aujourd'hui", false);
    }

    // --- the carousel -------------------------------------------------------------

    public function test_the_timeline_is_a_carousel_that_opens_on_the_next_test(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->subMonth()->toDateString(), 'test' => 'Passé 1'],
            ['date' => Carbon::today()->subWeek()->toDateString(), 'test' => 'Passé 2'],
            ['date' => Carbon::today()->addWeek()->toDateString(), 'test' => 'Prochain'],
            ['date' => Carbon::today()->addMonth()->toDateString(), 'test' => 'Plus tard'],
        ])]);

        $html = $this->get('/allemand')->assertOk()->assertSee('class="carousel"', false)->getContent();

        // Exactly one card is marked, and it is the first one not behind us.
        $this->assertSame(1, substr_count($html, 'data-timeline-next'));
        $this->assertMatchesRegularExpression('/data-timeline-next.*?Prochain/s', $html);

        // « Passé 2 » is the entry just before it, so the marker must not be there.
        $this->assertDoesNotMatchRegularExpression('/data-timeline-next.*?Passé 2/s', $html);
    }

    public function test_the_carousel_opens_on_the_index_of_the_next_test(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->subMonth()->toDateString(), 'test' => 'Passé 1'],
            ['date' => Carbon::today()->subWeek()->toDateString(), 'test' => 'Passé 2'],
            ['date' => Carbon::today()->addWeek()->toDateString(), 'test' => 'Prochain'],
            ['date' => Carbon::today()->addMonth()->toDateString(), 'test' => 'Plus tard'],
        ])]);

        // Third of four, so index 2 — the server hands Alpine the number
        // rather than making it hunt for the card.
        $this->get('/allemand')
            ->assertOk()
            ->assertSee('carousel({ start: 2, count: 4 })', false);
    }

    public function test_the_carousel_carries_a_pair_of_arrows(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->addWeek()->toDateString(), 'test' => 'Prochain'],
        ])]);

        $this->get('/allemand')
            ->assertOk()
            // The behaviour is declared on the markup: Alpine holds the index,
            // the arrows move it, and each greys out at its own end.
            ->assertSee('x-on:click="prev()"', false)
            ->assertSee('x-on:click="next()"', false)
            ->assertSee('x-bind:disabled="atStart"', false)
            ->assertSee('x-bind:disabled="atEnd"', false)
            ->assertSee('x-bind:style="track"', false)
            // How many cards are on screen is the stylesheet's call; the
            // component reads it back, so the markup only carries the index.
            ->assertSee('x-bind:data-ready="ready"', false)
            ->assertSee('x-on:resize.window="measure()"', false)
            // They drive the list they sit on, and say so out loud.
            ->assertSee('aria-controls="timeline"', false)
            ->assertSee('aria-label="Date précédente"', false)
            ->assertSee('aria-label="Date suivante"', false)
            // Each names its side: the full-height band either end of the run
            // fades towards the cards, so the two are not interchangeable.
            ->assertSee('carousel__arrow carousel__arrow--prev', false)
            ->assertSee('carousel__arrow carousel__arrow--next', false);
    }

    public function test_the_arrows_are_not_rendered_without_a_programme(): void
    {
        $this->get('/anglais')
            ->assertOk()
            ->assertSee('Aucune date')
            ->assertDontSee('class="carousel"', false)
            ->assertDontSee('x-on:click="next()"', false);
    }

    public function test_today_counts_as_the_next_test_rather_than_a_past_one(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->subWeek()->toDateString(), 'test' => 'Passé'],
            ['date' => Carbon::today()->toDateString(), 'test' => "Aujourd'hui"],
            ['date' => Carbon::today()->addWeek()->toDateString(), 'test' => 'Plus tard'],
        ])]);

        $html = $this->get('/allemand')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-timeline-next.*?Aujourd/s', $html);
        $this->assertSame(1, substr_count($html, 'data-timeline-next'));
    }

    public function test_a_year_that_is_entirely_over_marks_nothing(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => Carbon::today()->subMonth()->toDateString(), 'test' => 'Fini'],
        ])]);

        // Nothing is marked as next, and the carousel opens on the last card.
        $this->get('/allemand')
            ->assertOk()
            ->assertSee('carousel({ start: 0, count: 1 })', false)
            ->assertDontSee('data-timeline-next', false);
    }

    // --- pasting a programme ----------------------------------------------------

    public function test_an_administrator_pastes_a_programme(): void
    {
        $this->beAdmin();

        $this->get('/allemand/programme')->assertOk()->assertSee('Programme (JSON)');

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => '2026-10-06', 'test' => 'Test 2', 'chapters' => ['II. Die Wohnung']],
            ['date' => '2026-09-22', 'test' => 'Test 1', 'chapters' => ['Voc 1'], 'pages' => ['Verbes forts, p. 12']],
        ])])->assertRedirect('/allemand')->assertSessionHas('success');

        $entries = $this->german()->programEntries()->with('units')->get();

        // Sorted by date whatever order they were pasted in.
        $this->assertSame(['Test 1', 'Test 2'], $entries->pluck('title')->all());
        $this->assertSame('2026-09-22', $entries[0]->date->toDateString());

        $this->assertSame(
            ['Verbes forts, p. 12', 'Voc 1'],
            $entries[0]->units->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_a_name_with_no_unit_yet_creates_an_empty_one(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => '2026-10-06', 'chapters' => ['II. Die Wohnung'], 'pages' => ['Verbes forts, p. 12']],
        ])])->assertSessionHas('success');

        $chapter = Unit::where('name', 'II. Die Wohnung')->firstOrFail();
        $page = Unit::where('name', 'Verbes forts, p. 12')->firstOrFail();

        $this->assertSame(UnitKind::Vocabulary, $chapter->kind);
        $this->assertSame(UnitKind::Verbs, $page->kind);
        $this->assertTrue($chapter->isEmpty());

        // Empty, so the deck page offers the import editor rather than cards.
        $this->get(route('deck.show', [$this->german(), 'vocabulaire', $chapter], absolute: false))
            ->assertOk()
            ->assertSee('Ce chapitre est vide.');
    }

    public function test_an_existing_unit_is_matched_by_name_and_never_duplicated(): void
    {
        $this->beAdmin();
        $before = Unit::count();

        $this->post('/allemand/programme', ['program' => $this->program([
            // Same name, different case: still the same chapter.
            ['date' => '2026-10-06', 'chapters' => ['voc 1']],
        ])])->assertSessionHas('success');

        $this->assertSame($before, Unit::count());
        $this->assertSame(
            $this->german()->units()->where('kind', UnitKind::Vocabulary)->firstOrFail()->id,
            ProgramEntry::firstOrFail()->units()->firstOrFail()->id,
        );
    }

    public function test_pasting_replaces_the_whole_programme(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => '2026-09-22', 'test' => 'Ancien'],
        ])]);

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => '2026-10-06', 'test' => 'Nouveau'],
        ])]);

        $this->assertSame(['Nouveau'], $this->german()->programEntries()->pluck('title')->all());
    }

    public function test_the_edit_form_opens_on_the_programme_already_in_place(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([
            ['date' => '2026-09-22', 'test' => 'Test 1', 'chapters' => ['Voc 1']],
        ])]);

        $this->get('/allemand/programme')
            ->assertOk()
            ->assertSee('2026-09-22')
            ->assertSee('Test 1')
            ->assertSee('Voc 1');
    }

    public function test_a_programme_can_be_erased(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([['date' => '2026-09-22']])]);
        $this->delete('/allemand/programme')->assertRedirect('/allemand');

        $this->assertSame(0, ProgramEntry::count());
    }

    public function test_the_programme_belongs_to_one_language_only(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([['date' => '2026-09-22', 'test' => 'Test 1']])]);

        // Not `assertDontSee('Test 1')`: the entry form's own placeholder is
        // « Test 1 ». What must be absent is the timeline itself.
        $this->get('/anglais')
            ->assertOk()
            ->assertSee('Aucune date')
            ->assertDontSee('timeline__item', false);
    }

    // --- what is refused ---------------------------------------------------------

    public function test_it_rejects_a_programme_without_a_date(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([['test' => 'Sans date']])])
            ->assertSessionHasErrors('program');

        $this->assertSame(0, ProgramEntry::count());
    }

    public function test_it_rejects_an_impossible_date(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([['date' => '2026-02-31']])])
            ->assertSessionHasErrors('program');

        $this->post('/allemand/programme', ['program' => $this->program([['date' => '22/09/2026']])])
            ->assertSessionHasErrors('program');

        $this->assertSame(0, ProgramEntry::count());
    }

    public function test_it_rejects_malformed_json(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => '[{"date": '])->assertSessionHasErrors('program');
    }

    public function test_a_failed_paste_leaves_the_programme_alone(): void
    {
        $this->beAdmin();

        $this->post('/allemand/programme', ['program' => $this->program([['date' => '2026-09-22', 'test' => 'Test 1']])]);
        $this->post('/allemand/programme', ['program' => '[{"date": "pas une date"}]'])->assertSessionHasErrors('program');

        $this->assertSame(['Test 1'], $this->german()->programEntries()->pluck('title')->all());
    }

    // --- access -------------------------------------------------------------------

    public function test_a_plain_user_cannot_edit_the_programme(): void
    {
        $this->get('/allemand/programme')->assertForbidden();
        $this->post('/allemand/programme', ['program' => $this->program([['date' => '2026-09-22']])])->assertForbidden();
        $this->delete('/allemand/programme')->assertForbidden();

        $this->assertSame(0, ProgramEntry::count());
    }

    public function test_the_edit_link_is_only_shown_to_an_administrator(): void
    {
        $this->get('/allemand')->assertOk()->assertDontSee(route('program.edit', 'allemand'), false);

        $this->beAdmin();

        $this->get('/allemand')->assertOk()->assertSee(route('program.edit', 'allemand'), false);
    }

    public function test_guests_cannot_reach_a_language_page(): void
    {
        $this->post('/logout');

        $this->get('/allemand')->assertRedirect('/login');
        $this->get('/allemand/programme')->assertRedirect('/login');
    }
}
