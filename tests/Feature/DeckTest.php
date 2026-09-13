<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardState;
use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The card game itself — the module every language shares.
 */
class DeckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->post('/login', ['username' => 'anna']);
    }

    private function german(): Language
    {
        return Language::where('slug', 'allemand')->firstOrFail();
    }

    private function unit(): Unit
    {
        // Chapters and verb pages are numbered apart, so the kind has to be
        // named: `position = 1` alone matches one of each.
        return $this->german()->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();
    }

    private function deckUrl(?Unit $unit = null): string
    {
        $unit ??= $this->unit();

        return route('deck.show', [$unit->language, $unit->kind->slug(), $unit], absolute: false);
    }

    private function firstCard(): Card
    {
        return $this->unit()->cards()->firstOrFail();
    }

    // --- the seeded deck ------------------------------------------------------

    public function test_it_seeds_the_whole_deck_in_its_original_order(): void
    {
        // Two shipped chapters now, so every figure names the one it is about.
        $unit = $this->unit();
        $cards = $unit->cards()->get();

        $this->assertSame('Voc 1', $unit->name);
        $this->assertSame(146, $cards->count());
        $this->assertSame(570, Card::count());

        $first = $cards->first();
        $this->assertSame('der Mensch, en, en', $first->term);
        $this->assertSame("l'homme, l'être humain", $first->translation);
        $this->assertSame('Personalien', $first->section);

        $last = $cards->last();
        $this->assertSame('verstecken (+ prép. sans dépl.)', $last->term);
        $this->assertStringContainsString('Il cacha', $last->example);

        $this->assertSame(40, $cards->whereNotNull('example')->count());
        $this->assertSame(6, $cards->pluck('section')->unique()->count());
    }

    public function test_the_second_chapter_holds_the_pages_up_to_3_1(): void
    {
        $unit = $this->german()->units()->where('name', 'Voc 2')->firstOrFail();
        $cards = $unit->cards()->get();

        // Seuls les mots surlignés du manuel sont repris.
        $this->assertSame(179, $cards->count());
        $this->assertSame('die Gesundheit, /', $cards->first()->term);
        $this->assertSame('die Zigarre, n', $cards->last()->term);
        $this->assertContains('3.1 Speisen, Getränke', $cards->pluck('section')->unique()->all());
        $this->assertNotContains('der Atem, /', $cards->pluck('term')->all());
    }

    // --- the page -------------------------------------------------------------

    public function test_the_deck_page_plays_the_unit(): void
    {
        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee($this->unit()->name)
            ->assertSee('Je sais')
            ->assertSee('À revoir')
            ->assertSee('id="deck-data"', false)
            ->assertSee('der Mensch, en, en');
    }

    public function test_the_board_is_laid_out_in_the_shipped_order(): void
    {
        $html = $this->get($this->deckUrl())->assertOk()->getContent();

        // Title and reading direction share a row; the tools and the filters
        // sit above the card, the readout and the decisions below it.
        $order = ['deck-head__title', 'dir-pill', 'deck__tools', 'deck__filters', 'deck__stage', 'deck__meta', 'deck__actions'];

        $at = array_map(fn (string $hook) => strpos($html, $hook), $order);

        $this->assertSame($at, array_values(array_filter($at)), 'a landmark is missing from the board');

        $sorted = $at;
        sort($sorted);
        $this->assertSame($sorted, $at, 'the board is not in the order it ships in: '.implode(' → ', $order));

        // The filters are one joined bar with their counts inline.
        $this->assertStringContainsString('deck__filters btn-group', $html);
        $this->assertStringContainsString('deck__filter-count', $html);
        $this->assertStringNotContainsString('bento', $html);
    }

    public function test_the_card_faces_are_named_after_the_language(): void
    {
        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee('Deutsch')
            ->assertSee('FR → DE')
            ->assertSee('DE → FR')
            ->assertSee('data-direction="native" aria-pressed="true"', false);
    }

    public function test_a_second_language_gets_the_same_game_with_its_own_labels(): void
    {
        $english = Language::where('slug', 'anglais')->firstOrFail();
        $unit = $english->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();

        $this->assertSame('Voc Unit 1', $unit->name);
        $this->assertSame(123, $unit->cards()->count());

        $this->get($this->deckUrl($unit))
            ->assertOk()
            ->assertSee('Voc Unit 1')
            ->assertSee('English')
            ->assertSee('FR → EN')
            ->assertSee('ambitious')
            ->assertSee('Je sais')
            ->assertDontSee('Deutsch');
    }

    public function test_a_page_of_verbs_plays_the_same_game(): void
    {
        $unit = $this->german()->units()->where('kind', UnitKind::Verbs)->firstOrFail();
        $cards = $unit->cards()->get();

        // The infinitive is the card; the three forms ride along as its
        // example, exactly as the irregular verbs in the word lists do.
        $this->assertSame('Verben 1', $unit->name);
        $this->assertSame(61, $cards->count());
        $this->assertSame('abbiegen', $cards->first()->term);
        $this->assertSame('tourner, obliquer', $cards->first()->translation);
        $this->assertSame('biegt ab, bog ab, ist abgebogen', $cards->first()->example);
        $this->assertSame('lügen', $cards->last()->term);

        // The second page carries on where the first stops.
        $second = $this->german()->units()->where('name', 'Verben 2')->firstOrFail();
        $this->assertSame(61, $second->cards()->count());
        $this->assertSame('mitnehmen', $second->cards()->firstOrFail()->term);

        $this->get($this->deckUrl($unit))
            ->assertOk()
            ->assertSee('Verben 1')
            ->assertSee('abbiegen')
            ->assertSee('Je sais')
            // The conjugation is the lesson on a verb page, so the board says
            // so and the stylesheet gives it the room.
            ->assertSee('deck--verbs', false);

        $this->assertStringContainsString('/allemand/verbes/', $this->deckUrl($unit));
    }

    public function test_a_chapter_does_not_get_the_verb_typography(): void
    {
        $this->get($this->deckUrl())->assertOk()->assertDontSee('deck--verbs', false);
    }

    public function test_the_vocabulary_list_opens_a_full_screen_modal_with_a_search(): void
    {
        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee('data-modal-open="vocab"', false)
            ->assertSee('id="vocab"', false)
            ->assertSee('modal--full', false)
            ->assertSee('id="vocabSearch"', false)
            ->assertSee('data-modal-close', false);
    }

    public function test_the_search_does_not_grab_focus_when_the_list_opens(): void
    {
        // `showModal()` focuses the first `autofocus` element, else the first
        // focusable one — which is the close button. An autofocused search box
        // would throw the keyboard up over the words on every phone.
        $html = $this->get($this->deckUrl())->assertOk()->getContent();

        $modal = substr($html, strpos($html, 'id="vocab"'));

        $this->assertStringNotContainsString('autofocus', substr($modal, 0, strpos($modal, '</dialog>')));
    }

    public function test_shuffling_has_a_visible_celebration(): void
    {
        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee('id="shuffleFx"', false)
            ->assertSee('shuffle-fx__icon', false)
            // Announced for anyone who cannot see the confetti.
            ->assertSee('id="shuffleSay"', false)
            ->assertSee('role="status"', false);
    }

    public function test_the_rail_lists_the_units_of_the_same_kind_only(): void
    {
        $verbs = $this->german()->units()->where('kind', UnitKind::Verbs)->firstOrFail();

        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee($this->unit()->name)
            ->assertDontSee($verbs->name);
    }

    // --- addressing -----------------------------------------------------------

    public function test_a_unit_cannot_be_opened_under_the_wrong_language(): void
    {
        $unit = $this->unit();

        $this->get("/anglais/vocabulaire/{$unit->id}")->assertNotFound();
    }

    public function test_a_unit_cannot_be_opened_under_the_wrong_kind(): void
    {
        $unit = $this->unit();

        $this->get("/allemand/verbes/{$unit->id}")->assertNotFound();
        $this->get("/allemand/grammaire/{$unit->id}")->assertNotFound();
    }

    public function test_an_unknown_language_is_a_404(): void
    {
        $this->get('/klingon')->assertNotFound();
    }

    public function test_opening_a_deck_remembers_the_language_and_the_unit(): void
    {
        $this->get($this->deckUrl())->assertOk();

        $this->assertSame($this->german()->id, session('language_id'));
        $this->assertSame($this->unit()->id, session('unit_id'));
    }

    // --- what the user knows --------------------------------------------------

    public function test_it_remembers_known_and_review_cards_across_sessions(): void
    {
        $card = $this->firstCard();
        $other = $this->unit()->cards()->skip(1)->firstOrFail();

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertOk();
        $this->postJson("/cards/{$other->id}/status", ['status' => 'review'])->assertOk();
        $this->post('/logout');

        // A new session under the same name: the marks come back.
        $this->post('/login', ['username' => 'anna']);
        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee('"'.$card->id.'":"known"', false)
            ->assertSee('"'.$other->id.'":"review"', false);
    }

    public function test_it_keeps_each_user_progress_separate(): void
    {
        $card = $this->firstCard();

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);
        $this->post('/logout');

        $this->post('/login', ['username' => 'ben']);
        $this->get($this->deckUrl())->assertOk()->assertDontSee('"'.$card->id.'":"known"', false);

        $this->assertSame(1, CardState::count());
    }

    public function test_it_moves_a_card_from_review_to_known_without_duplicating_the_row(): void
    {
        $card = $this->firstCard();

        $this->postJson("/cards/{$card->id}/status", ['status' => 'review']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);

        $this->assertSame(1, CardState::count());
        $this->assertSame('known', CardState::firstOrFail()->status);
    }

    public function test_it_clears_a_card_status_when_null_is_posted(): void
    {
        $card = $this->firstCard();

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);
        $this->postJson("/cards/{$card->id}/status", ['status' => null])->assertOk();

        $this->assertSame(0, CardState::count());
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $this->postJson("/cards/{$this->firstCard()->id}/status", ['status' => 'maybe'])->assertStatus(422);
    }

    public function test_it_resets_every_mark_for_the_signed_in_user_only(): void
    {
        $card = $this->firstCard();

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);
        $this->post('/logout');

        $this->post('/login', ['username' => 'anna']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'review']);
        $this->postJson('/cards/reset')->assertOk();

        $this->assertSame(1, CardState::count());
        $this->assertSame('ben', CardState::firstOrFail()->user->username);
    }

    public function test_it_refuses_to_store_a_status_for_a_guest(): void
    {
        $card = $this->firstCard();
        $this->post('/logout');

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertStatus(401);
        $this->assertSame(0, CardState::count());
    }

    public function test_an_empty_filter_shows_a_glyph_rather_than_the_words(): void
    {
        // Nothing is marked yet, so « Connus » and « À revoir » are both empty.
        // The face carries the icon — hidden until `deck.js` needs it — and the
        // wording survives only as its label, for anyone who cannot see it.
        $this->get($this->deckUrl())
            ->assertOk()
            ->assertSee('id="frontEmpty" hidden', false)
            ->assertSee('flashcard__empty', false)
            ->assertSee('aria-label="Liste vide"', false)
            ->assertDontSee('>Liste vide<', false);
    }

    public function test_the_rail_shows_how_far_the_user_has_got(): void
    {
        $this->postJson("/cards/{$this->firstCard()->id}/status", ['status' => 'known']);

        $this->get($this->deckUrl())->assertOk()->assertSee('1 / 146');
    }
}
