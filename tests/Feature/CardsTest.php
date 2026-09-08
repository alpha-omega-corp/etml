<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function firstCard(): Card
    {
        return Card::orderBy('position')->firstOrFail();
    }

    public function test_it_seeds_the_whole_deck_in_its_original_order(): void
    {
        $this->assertSame(146, Card::count());

        $first = $this->firstCard();
        $this->assertSame('der Mensch, en, en', $first->de);
        $this->assertSame("l'homme, l'être humain", $first->fr);
        $this->assertSame('Personalien', $first->section);

        $last = Card::orderByDesc('position')->firstOrFail();
        $this->assertSame('verstecken (+ prép. sans dépl.)', $last->de);
        $this->assertStringContainsString('Il cacha', $last->example);

        $this->assertSame(40, Card::whereNotNull('example')->count());
        $this->assertSame(6, Card::distinct()->count('section'));
    }

    public function test_the_vocabulary_list_opens_a_full_screen_modal_with_a_search(): void
    {
        $this->post('/login', ['username' => 'anna']);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-modal-open="vocab"', false)
            ->assertSee('Tout le vocabulaire')
            ->assertSee('id="vocab"', false)
            ->assertSee('modal--full', false)
            ->assertSee('id="vocabSearch"', false)
            ->assertSee('data-modal-close', false);
    }

    public function test_shuffling_has_a_visible_celebration(): void
    {
        $this->post('/login', ['username' => 'anna']);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="shuffleFx"', false)
            ->assertSee('shuffle-fx__icon', false)
            // Announced for anyone who cannot see the confetti.
            ->assertSee('id="shuffleSay"', false)
            ->assertSee('role="status"', false);
    }

    public function test_it_sends_guests_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_it_creates_the_user_on_first_sign_in_and_reuses_it_afterwards(): void
    {
        $this->post('/login', ['username' => 'anna'])->assertRedirect('/');
        $this->assertSame(1, User::where('username', 'anna')->count());

        $this->post('/logout');
        $this->post('/login', ['username' => 'anna'])->assertRedirect('/');
        $this->assertSame(1, User::count());
    }

    public function test_it_rejects_an_empty_username(): void
    {
        $this->post('/login', ['username' => ''])->assertSessionHasErrors('username');
        $this->assertSame(0, User::count());
    }

    public function test_it_remembers_known_and_review_cards_across_sessions(): void
    {
        $card = $this->firstCard();
        $other = Card::orderBy('position')->skip(1)->firstOrFail();

        $this->post('/login', ['username' => 'anna']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertOk();
        $this->postJson("/cards/{$other->id}/status", ['status' => 'review'])->assertOk();
        $this->post('/logout');

        // Nouvelle session, même nom : les statuts reviennent.
        $this->post('/login', ['username' => 'anna']);
        $this->get('/')
            ->assertOk()
            ->assertSee('"'.$card->id.'":"known"', false)
            ->assertSee('"'.$other->id.'":"review"', false);
    }

    public function test_it_keeps_each_user_progress_separate(): void
    {
        $card = $this->firstCard();

        $this->post('/login', ['username' => 'anna']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);
        $this->post('/logout');

        $this->post('/login', ['username' => 'ben']);
        $this->get('/')->assertOk()->assertDontSee('"'.$card->id.'":"known"', false);

        $this->assertSame(1, CardState::count());
    }

    public function test_it_moves_a_card_from_review_to_known_without_duplicating_the_row(): void
    {
        $card = $this->firstCard();

        $this->post('/login', ['username' => 'anna']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'review']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);

        $this->assertSame(1, CardState::count());
        $this->assertSame('known', CardState::firstOrFail()->status);
    }

    public function test_it_clears_a_card_status_when_null_is_posted(): void
    {
        $card = $this->firstCard();

        $this->post('/login', ['username' => 'anna']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known']);
        $this->postJson("/cards/{$card->id}/status", ['status' => null])->assertOk();

        $this->assertSame(0, CardState::count());
    }

    public function test_it_rejects_an_unknown_status(): void
    {
        $card = $this->firstCard();

        $this->post('/login', ['username' => 'anna']);
        $this->postJson("/cards/{$card->id}/status", ['status' => 'maybe'])->assertStatus(422);
    }

    public function test_it_resets_every_mark_for_the_signed_in_user_only(): void
    {
        $card = $this->firstCard();

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

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertStatus(401);
        $this->assertSame(0, CardState::count());
    }
}
