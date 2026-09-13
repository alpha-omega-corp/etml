<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use App\Support\WordList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chapters and verb pages: created from a pasted word list, renamed in place,
 * filled when they were left empty.
 */
class UnitsTest extends TestCase
{
    use RefreshDatabase;

    private const MAP = '{"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}';

    /**
     * How many units the seed leaves behind: the decks, plus the empty
     * chapters the seeded programme points at. Counted rather than written
     * down, so adding a deck or a date does not break every refusal test.
     */
    private int $seededUnits;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seededUnits = Unit::count();
        $this->post('/login', ['username' => 'anna']);
    }

    private function german(): Language
    {
        return Language::where('slug', 'allemand')->firstOrFail();
    }

    private function unit(): Unit
    {
        return $this->german()->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();
    }

    private function empty(UnitKind $kind = UnitKind::Vocabulary, string $name = 'Vide'): Unit
    {
        return Unit::create([
            'language_id' => $this->german()->id,
            'kind' => $kind,
            'name' => $name,
            'position' => Unit::nextPosition($this->german(), $kind),
        ]);
    }

    private function createUrl(string $kind = 'vocabulaire'): string
    {
        return route('units.create', ['language' => 'allemand', 'kind' => $kind], absolute: false);
    }

    // --- the creation page ----------------------------------------------------

    public function test_the_creation_page_carries_the_editor_and_the_claude_template(): void
    {
        $this->get($this->createUrl())
            ->assertOk()
            ->assertSee('Nouveau chapitre')
            ->assertSee('Mots (JSON)')
            ->assertSee('Modèle à coller dans Claude')
            ->assertSee('Transcris chaque entrée en JSON')
            ->assertSee('data-copy="claudePrompt"', false)
            ->assertSee('Formats acceptés');
    }

    public function test_the_creation_page_speaks_of_pages_for_verbs(): void
    {
        // French agreement comes from the enum, so the verbs form never reads
        // « Nouveau page ».
        $this->get($this->createUrl('verbes'))
            ->assertOk()
            ->assertSee('Nouvelle page')
            ->assertSee('Nom de la page')
            ->assertDontSee('Nouveau page')
            ->assertDontSee('chapitre');
    }

    public function test_the_claude_template_asks_for_the_shape_the_parser_reads(): void
    {
        $html = $this->get($this->createUrl())->assertOk()->getContent();

        // The template's own example must survive a round trip through the parser.
        preg_match('/\[\s*\{&quot;fr&quot;.*?\]/s', $html, $m);
        $this->assertNotEmpty($m, 'the template shows no JSON example');

        $rows = WordList::parse(html_entity_decode($m[0], ENT_QUOTES), 'de');

        $this->assertSame('la maison', $rows[0]['translation']);
        $this->assertSame('das Haus, ¨er', $rows[0]['term']);
        $this->assertSame('Die Wohnung', $rows[0]['section']);
    }

    public function test_the_template_is_written_in_the_language_of_the_deck(): void
    {
        $this->get(route('units.create', ['language' => 'anglais', 'kind' => 'vocabulaire'], absolute: false))
            ->assertOk()
            ->assertSee('&quot;en&quot;', false)
            ->assertSee('the house')
            ->assertDontSee('das Haus');
    }

    public function test_guests_cannot_open_the_creation_page(): void
    {
        $this->post('/logout');

        $this->get($this->createUrl())->assertRedirect('/login');
    }

    // --- creating from JSON ---------------------------------------------------

    public function test_it_creates_a_chapter_and_its_cards_from_a_map(): void
    {
        $this->post('/units', ['language' => 'allemand', 'kind' => 'vocabulaire', 'name' => 'II. Die Wohnung', 'words' => self::MAP])
            ->assertSessionHas('success');

        $unit = Unit::where('name', 'II. Die Wohnung')->firstOrFail();
        $this->assertSame($this->german()->id, $unit->language_id);
        $this->assertSame(UnitKind::Vocabulary, $unit->kind);
        // Behind the two seeded decks and the eight chapters the programme
        // reserved for the rest of the year.
        $this->assertSame(11, $unit->position);

        $cards = $unit->cards()->get();
        $this->assertCount(2, $cards);
        $this->assertSame('la maison', $cards[0]->translation);
        $this->assertSame('das Haus, ¨er', $cards[0]->term);
        $this->assertSame(0, $cards[0]->position);
        $this->assertSame('la cuisine', $cards[1]->translation);
        $this->assertSame(1, $cards[1]->position);

        // The new chapter is immediately quizzable.
        $this->get(route('deck.show', [$this->german(), 'vocabulaire', $unit], absolute: false))
            ->assertOk()
            ->assertSee('Je sais')
            ->assertSee('la maison');
    }

    public function test_it_creates_a_page_of_verbs(): void
    {
        $this->post('/units', [
            'language' => 'allemand',
            'kind' => 'verbes',
            'name' => 'Verbes forts, p. 12',
            'words' => '{"aller": "gehen – ging – ist gegangen"}',
        ])->assertSessionHas('success');

        $unit = Unit::where('name', 'Verbes forts, p. 12')->firstOrFail();

        $this->assertSame(UnitKind::Verbs, $unit->kind);
        // Verb pages and chapters are numbered apart: this lands behind the
        // two shipped verb pages, not behind the two chapters.
        $this->assertSame(3, $unit->position);
        $this->assertSame('gehen – ging – ist gegangen', $unit->cards()->firstOrFail()->term);
    }

    public function test_it_accepts_the_long_list_form_with_examples_and_sections(): void
    {
        $json = json_encode([
            ['fr' => 'la maison', 'de' => 'das Haus, ¨er', 'ex' => 'zu Hause', 'sec' => 'Die Wohnung'],
            ['french' => 'la cuisine', 'term' => 'die Küche, n'],
        ], JSON_UNESCAPED_UNICODE);

        $this->post('/units', ['language' => 'allemand', 'kind' => 'vocabulaire', 'name' => 'II. Die Wohnung', 'words' => $json]);

        $cards = Unit::where('name', 'II. Die Wohnung')->firstOrFail()->cards()->get();
        $this->assertSame('zu Hause', $cards[0]->example);
        $this->assertSame('Die Wohnung', $cards[0]->section);
        $this->assertSame('la cuisine', $cards[1]->translation);
        $this->assertNull($cards[1]->example);
    }

    public function test_a_list_is_keyed_by_the_language_code(): void
    {
        $this->post('/units', [
            'language' => 'anglais',
            'kind' => 'vocabulaire',
            'name' => 'Unit 2',
            'words' => '[{"fr": "la maison", "en": "the house"}]',
        ])->assertSessionHas('success');

        $this->assertSame('the house', Unit::where('name', 'Unit 2')->firstOrFail()->cards()->firstOrFail()->term);
    }

    public function test_a_list_keyed_by_another_language_is_rejected(): void
    {
        $this->post('/units', [
            'language' => 'anglais',
            'kind' => 'vocabulaire',
            'name' => 'Unit 2',
            'words' => '[{"fr": "la maison", "de": "das Haus"}]',
        ])->assertSessionHasErrors('words');

        $this->assertNull(Unit::where('name', 'Unit 2')->first());
    }

    public function test_it_trims_entries_and_skips_blank_ones(): void
    {
        $this->post('/units', [
            'language' => 'allemand',
            'kind' => 'vocabulaire',
            'name' => 'Espaces',
            'words' => '{"  la maison  ": "  das Haus  ", "": "vide", "rien": ""}',
        ]);

        $cards = Unit::where('name', 'Espaces')->firstOrFail()->cards()->get();
        $this->assertCount(1, $cards);
        $this->assertSame('la maison', $cards[0]->translation);
        $this->assertSame('das Haus', $cards[0]->term);
    }

    public function test_it_rejects_malformed_json(): void
    {
        $this->post('/units', ['language' => 'allemand', 'name' => 'Cassé', 'words' => '{"la maison": '])
            ->assertSessionHasErrors('words');

        $this->assertSame($this->seededUnits, Unit::count());
    }

    public function test_it_rejects_a_json_value_that_is_not_a_string(): void
    {
        $this->post('/units', ['language' => 'allemand', 'name' => 'Cassé', 'words' => '{"la maison": ["das Haus"]}'])
            ->assertSessionHasErrors('words');

        $this->assertSame($this->seededUnits, Unit::count());
    }

    public function test_it_rejects_a_list_entry_missing_a_side(): void
    {
        $this->post('/units', ['language' => 'allemand', 'name' => 'Cassé', 'words' => '[{"fr": "la maison"}]'])
            ->assertSessionHasErrors('words');
    }

    public function test_it_rejects_an_empty_word_list(): void
    {
        $this->post('/units', ['language' => 'allemand', 'name' => 'Vide', 'words' => '{}'])
            ->assertSessionHasErrors('words');

        $this->assertSame($this->seededUnits, Unit::count());
    }

    public function test_it_requires_a_name_and_a_list(): void
    {
        $this->post('/units', ['language' => 'allemand', 'name' => '', 'words' => self::MAP])->assertSessionHasErrors('name');
        $this->post('/units', ['language' => 'allemand', 'name' => 'Sans mots', 'words' => ''])->assertSessionHasErrors('words');

        $this->assertSame($this->seededUnits, Unit::count());
    }

    public function test_it_refuses_more_than_the_entry_cap(): void
    {
        $words = [];
        for ($i = 0; $i <= WordList::MAX_ENTRIES; $i++) {
            $words["mot {$i}"] = "wort {$i}";
        }

        $this->post('/units', ['language' => 'allemand', 'name' => 'Trop', 'words' => json_encode($words)])
            ->assertSessionHasErrors('words');

        $this->assertSame($this->seededUnits, Unit::count());
    }

    // --- filling a unit that was left empty -----------------------------------

    public function test_an_empty_unit_offers_the_json_editor(): void
    {
        $unit = $this->empty();

        $this->get(route('deck.show', [$this->german(), 'vocabulaire', $unit], absolute: false))
            ->assertOk()
            ->assertSee('Ce chapitre est vide.')
            ->assertSee('Créer les cartes')
            ->assertSee('Modèle à coller dans Claude')
            ->assertDontSee('Je sais');
    }

    public function test_it_fills_an_empty_unit_from_json(): void
    {
        $unit = $this->empty();

        $this->post("/units/{$unit->id}/import", ['import' => self::MAP])
            ->assertRedirect(route('deck.show', [$this->german(), 'vocabulaire', $unit], absolute: false))
            ->assertSessionHas('success');

        $this->assertSame(2, $unit->cards()->count());
    }

    public function test_it_refuses_to_import_into_a_unit_that_already_has_words(): void
    {
        $unit = $this->unit();

        $this->post("/units/{$unit->id}/import", ['import' => self::MAP])
            ->assertSessionHasErrors('import');

        $this->assertSame(146, $unit->cards()->count());
    }

    public function test_a_failed_import_leaves_the_unit_untouched(): void
    {
        $unit = $this->empty();

        $this->post("/units/{$unit->id}/import", ['import' => '{"la maison": '])
            ->assertSessionHasErrors('import');

        $this->assertSame(0, $unit->cards()->count());
        $this->assertSame(570, Card::count());
    }

    // --- renaming --------------------------------------------------------------

    public function test_the_rail_carries_an_inline_rename_control(): void
    {
        $unit = $this->unit();

        $this->get(route('deck.show', [$this->german(), 'vocabulaire', $unit], absolute: false))
            ->assertOk()
            ->assertSee('data-rename-url="'.route('units.update', $unit).'"', false)
            ->assertSee('data-rename-edit', false)
            ->assertSee('data-rename-input', false)
            ->assertSee('Renommer « '.e($unit->name).' »', false);
    }

    public function test_it_renames_from_the_rail_and_answers_with_the_new_name(): void
    {
        $unit = $this->unit();

        $this->patchJson("/units/{$unit->id}", ['rename' => 'Vocabulaire I'])
            ->assertOk()
            ->assertExactJson(['id' => $unit->id, 'name' => 'Vocabulaire I']);

        $this->assertSame('Vocabulaire I', $unit->fresh()->name);
        $this->assertSame(146, $unit->cards()->count());
    }

    public function test_an_inline_rename_rejects_a_too_short_name(): void
    {
        $unit = $this->unit();
        $before = $unit->name;

        $this->patchJson("/units/{$unit->id}", ['rename' => 'a'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rename');

        $this->assertSame($before, $unit->fresh()->name);
    }

    public function test_renaming_trims_and_keeps_the_words(): void
    {
        $unit = $this->unit();

        $this->patch("/units/{$unit->id}", ['rename' => '   Vocabulaire I   ']);

        $this->assertSame('Vocabulaire I', $unit->fresh()->name);
        $this->assertSame(146, $unit->cards()->count());
    }

    public function test_it_rejects_an_empty_new_name(): void
    {
        $unit = $this->unit();
        $before = $unit->name;

        $this->patch("/units/{$unit->id}", ['rename' => ''])->assertSessionHasErrors('rename');

        $this->assertSame($before, $unit->fresh()->name);
    }

    // --- access ----------------------------------------------------------------

    public function test_guests_cannot_reach_unit_routes(): void
    {
        $unit = $this->unit();
        $this->post('/logout');

        $this->get($this->createUrl())->assertRedirect('/login');
        $this->post('/units', ['language' => 'allemand', 'name' => 'X', 'words' => self::MAP])->assertRedirect('/login');
        $this->patch("/units/{$unit->id}", ['rename' => 'Pirate'])->assertRedirect('/login');
        $this->post("/units/{$unit->id}/import", ['import' => self::MAP])->assertRedirect('/login');

        $this->assertSame($this->seededUnits, Unit::count());
    }
}
