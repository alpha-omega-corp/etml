<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Card;
use App\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChaptersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->post('/login', ['username' => 'anna']);
    }

    private function allemand(): Branch
    {
        return Branch::where('slug', 'allemand')->firstOrFail();
    }

    private function german(): Chapter
    {
        return $this->allemand()->chapters()->firstOrFail();
    }

    private const MAP = '{"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}';

    public function test_the_seeded_deck_belongs_to_the_first_chapter(): void
    {
        $chapter = $this->german();

        $this->assertSame('I. Der Mensch — Personalien & Familie', $chapter->name);
        $this->assertSame(146, $chapter->cards()->count());
    }

    public function test_it_lists_chapters_and_marks_the_current_one(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Chapitres')
            ->assertSee($this->german()->name)
            ->assertSee('aria-current="page"', false);
    }

    // --- the creation page ----------------------------------------------------

    public function test_the_sidebar_links_to_the_creation_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('chapters.create'), false)
            ->assertSee('Nouveau chapitre');
    }

    public function test_the_creation_page_carries_the_editor_and_the_claude_template(): void
    {
        $this->get('/chapters/create')
            ->assertOk()
            ->assertSee('Nouveau chapitre')
            ->assertSee('Mots (JSON)')
            ->assertSee('Modèle à coller dans Claude')
            ->assertSee('Transcris chaque entrée en JSON')
            ->assertSee('data-copy="claudePrompt"', false)
            ->assertSee('Formats acceptés');
    }

    public function test_the_claude_template_asks_for_the_shape_the_parser_reads(): void
    {
        $html = $this->get('/chapters/create')->assertOk()->getContent();

        // The template's own example must survive a round trip through the parser.
        preg_match('/\[\s*\{&quot;fr&quot;.*?\]/s', $html, $m);
        $this->assertNotEmpty($m, 'the template shows no JSON example');

        $rows = \App\Support\WordList::parse(html_entity_decode($m[0], ENT_QUOTES));

        $this->assertCount(3, $rows);
        $this->assertSame('der Mensch, en, en', $rows[0]['de']);
        $this->assertSame('Personalien', $rows[0]['section']);
        $this->assertSame('stirbt, starb, ist gestorben', $rows[2]['example']);
    }

    public function test_an_empty_chapter_also_offers_the_claude_template(): void
    {
        $chapter = Chapter::create(['branch_id' => $this->allemand()->id, 'name' => 'Vide', 'position' => 2]);
        $this->get("/chapters/{$chapter->id}");

        $this->get('/')->assertOk()->assertSee('Modèle à coller dans Claude');
    }

    public function test_guests_cannot_open_the_creation_page(): void
    {
        $this->post('/logout');
        $this->get('/chapters/create')->assertRedirect('/login');
    }

    // --- creating from JSON --------------------------------------------------

    public function test_it_creates_a_chapter_and_its_cards_from_a_french_to_german_map(): void
    {
        $this->post('/chapters', ['name' => 'II. Die Wohnung', 'words' => self::MAP])
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $chapter = Chapter::where('name', 'II. Die Wohnung')->firstOrFail();
        $this->assertSame($this->allemand()->id, $chapter->branch_id);
        $this->assertSame($chapter->id, session('chapter_id'));

        $cards = $chapter->cards()->get();
        $this->assertCount(2, $cards);
        $this->assertSame('la maison', $cards[0]->fr);
        $this->assertSame('das Haus, ¨er', $cards[0]->de);
        $this->assertSame(0, $cards[0]->position);
        $this->assertSame('la cuisine', $cards[1]->fr);
        $this->assertSame(1, $cards[1]->position);

        // The new chapter is immediately quizzable.
        $this->get('/')->assertOk()->assertSee('Je sais')->assertSee('la maison');
    }

    public function test_it_accepts_the_long_list_form_with_examples_and_sections(): void
    {
        $json = json_encode([
            ['fr' => 'la maison', 'de' => 'das Haus, ¨er', 'ex' => 'zu Hause', 'sec' => 'Die Wohnung'],
            ['french' => 'la cuisine', 'german' => 'die Küche, n'],
        ], JSON_UNESCAPED_UNICODE);

        $this->post('/chapters', ['name' => 'II. Die Wohnung', 'words' => $json])->assertRedirect('/');

        $cards = Chapter::where('name', 'II. Die Wohnung')->firstOrFail()->cards()->get();
        $this->assertSame('zu Hause', $cards[0]->example);
        $this->assertSame('Die Wohnung', $cards[0]->section);
        $this->assertSame('la cuisine', $cards[1]->fr);
        $this->assertNull($cards[1]->example);
    }

    public function test_it_trims_entries_and_skips_blank_ones(): void
    {
        $this->post('/chapters', [
            'name' => 'Espaces',
            'words' => '{"  la maison  ": "  das Haus  ", "": "vide", "rien": ""}',
        ])->assertRedirect('/');

        $cards = Chapter::where('name', 'Espaces')->firstOrFail()->cards()->get();
        $this->assertCount(1, $cards);
        $this->assertSame('la maison', $cards[0]->fr);
        $this->assertSame('das Haus', $cards[0]->de);
    }

    public function test_it_rejects_malformed_json(): void
    {
        $this->post('/chapters', ['name' => 'Cassé', 'words' => '{"la maison": '])
            ->assertSessionHasErrors('words');

        $this->assertSame(1, Chapter::count());
    }

    public function test_it_rejects_a_json_value_that_is_not_a_string(): void
    {
        $this->post('/chapters', ['name' => 'Cassé', 'words' => '{"la maison": ["das Haus"]}'])
            ->assertSessionHasErrors('words');

        $this->assertSame(1, Chapter::count());
    }

    public function test_it_rejects_a_list_entry_missing_a_side(): void
    {
        $this->post('/chapters', ['name' => 'Cassé', 'words' => '[{"fr": "la maison"}]'])
            ->assertSessionHasErrors('words');
    }

    public function test_it_rejects_an_empty_word_list(): void
    {
        $this->post('/chapters', ['name' => 'Vide', 'words' => '{}'])
            ->assertSessionHasErrors('words');

        $this->assertSame(1, Chapter::count());
    }

    public function test_it_requires_a_name_and_a_list(): void
    {
        $this->post('/chapters', ['name' => '', 'words' => self::MAP])->assertSessionHasErrors('name');
        $this->post('/chapters', ['name' => 'Sans mots', 'words' => ''])->assertSessionHasErrors('words');

        $this->assertSame(1, Chapter::count());
    }

    public function test_it_refuses_more_than_the_entry_cap(): void
    {
        $words = [];
        for ($i = 0; $i <= \App\Support\WordList::MAX_ENTRIES; $i++) {
            $words["mot {$i}"] = "wort {$i}";
        }

        $this->post('/chapters', ['name' => 'Trop', 'words' => json_encode($words)])
            ->assertSessionHasErrors('words');

        $this->assertSame(1, Chapter::count());
    }

    // --- filling a chapter that was left empty --------------------------------

    public function test_an_empty_chapter_offers_the_json_editor(): void
    {
        $chapter = Chapter::create(['branch_id' => $this->allemand()->id, 'name' => 'Vide', 'position' => 2]);
        $this->get("/chapters/{$chapter->id}");

        $this->get('/')
            ->assertOk()
            ->assertSee('Ce chapitre est vide.')
            ->assertSee('Créer les cartes')
            ->assertDontSee('Je sais');
    }

    public function test_it_fills_an_empty_chapter_from_json(): void
    {
        $chapter = Chapter::create(['branch_id' => $this->allemand()->id, 'name' => 'Vide', 'position' => 2]);

        $this->post("/chapters/{$chapter->id}/import", ['import' => self::MAP])
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertSame(2, $chapter->cards()->count());
        $this->assertSame($chapter->id, session('chapter_id'));
    }

    public function test_it_refuses_to_import_into_a_chapter_that_already_has_words(): void
    {
        $chapter = $this->german();

        $this->post("/chapters/{$chapter->id}/import", ['import' => self::MAP])
            ->assertSessionHasErrors('import');

        $this->assertSame(146, $chapter->cards()->count());
    }

    public function test_a_failed_import_leaves_the_chapter_untouched(): void
    {
        $chapter = Chapter::create(['branch_id' => $this->allemand()->id, 'name' => 'Vide', 'position' => 2]);

        $this->post("/chapters/{$chapter->id}/import", ['import' => '{"la maison": '])
            ->assertSessionHasErrors('import');

        $this->assertSame(0, $chapter->cards()->count());
        $this->assertSame(146, Card::count());
    }

    // --- renaming -------------------------------------------------------------

    public function test_the_navigation_carries_an_inline_rename_control(): void
    {
        $chapter = $this->german();

        $this->get('/')
            ->assertOk()
            ->assertSee('data-chapter="'.$chapter->id.'"', false)
            ->assertSee('data-chapter-edit', false)
            ->assertSee('data-chapter-input', false)
            ->assertSee('Renommer « '.e($chapter->name).' »', false)
            // The old standalone form is gone.
            ->assertDontSee('Renommer le chapitre');
    }

    public function test_it_renames_from_the_navigation_and_answers_with_the_new_name(): void
    {
        $chapter = $this->german();

        $this->patchJson("/chapters/{$chapter->id}", ['rename' => 'Vocabulaire I'])
            ->assertOk()
            ->assertExactJson(['id' => $chapter->id, 'name' => 'Vocabulaire I']);

        $this->assertSame('Vocabulaire I', $chapter->fresh()->name);
        $this->assertSame(146, $chapter->cards()->count());
    }

    public function test_an_inline_rename_can_target_a_chapter_that_is_not_open(): void
    {
        $other = Chapter::create(['branch_id' => $this->allemand()->id, 'name' => 'Vide', 'position' => 2]);
        $open = $this->german();
        $this->get("/chapters/{$open->id}");

        $this->patchJson("/chapters/{$other->id}", ['rename' => 'Renommé'])->assertOk();

        $this->assertSame('Renommé', $other->fresh()->name);
        // Renaming another row must not move the deck.
        $this->assertSame($open->id, session('chapter_id'));
    }

    public function test_an_inline_rename_rejects_a_too_short_name(): void
    {
        $chapter = $this->german();
        $before = $chapter->name;

        $this->patchJson("/chapters/{$chapter->id}", ['rename' => 'a'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rename');

        $this->assertSame($before, $chapter->fresh()->name);
    }

    public function test_it_renames_a_chapter(): void
    {
        $chapter = $this->german();

        $this->patch("/chapters/{$chapter->id}", ['rename' => 'I. Der Mensch (révisé)'])
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertSame('I. Der Mensch (révisé)', $chapter->fresh()->name);
        $this->get('/')->assertOk()->assertSee('I. Der Mensch (révisé)');
    }

    public function test_renaming_trims_and_keeps_the_words(): void
    {
        $chapter = $this->german();

        $this->patch("/chapters/{$chapter->id}", ['rename' => '   Vocabulaire I   ']);

        $this->assertSame('Vocabulaire I', $chapter->fresh()->name);
        $this->assertSame(146, $chapter->cards()->count());
    }

    public function test_it_rejects_an_empty_new_name(): void
    {
        $chapter = $this->german();
        $before = $chapter->name;

        $this->patch("/chapters/{$chapter->id}", ['rename' => ''])->assertSessionHasErrors('rename');

        $this->assertSame($before, $chapter->fresh()->name);
    }

    public function test_renaming_does_not_light_up_the_create_form(): void
    {
        $chapter = $this->german();

        // The two forms sit on the same page, so their fields must not collide.
        $this->patch("/chapters/{$chapter->id}", ['rename' => ''])
            ->assertSessionHasErrors('rename')
            ->assertSessionDoesntHaveErrors(['name', 'words']);
    }

    // --- access ---------------------------------------------------------------

    public function test_guests_cannot_reach_chapter_routes(): void
    {
        $chapter = $this->german();
        $this->post('/logout');

        $this->get("/chapters/{$chapter->id}")->assertRedirect('/login');
        $this->post('/chapters', ['name' => 'X', 'words' => self::MAP])->assertRedirect('/login');
        $this->patch("/chapters/{$chapter->id}", ['rename' => 'Pirate'])->assertRedirect('/login');
        $this->post("/chapters/{$chapter->id}/import", ['import' => self::MAP])->assertRedirect('/login');

        $this->assertSame(1, Chapter::count());
    }
}
