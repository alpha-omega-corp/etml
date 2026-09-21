<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardState;
use App\Models\Language;
use App\Models\Unit;
use App\Models\User;
use App\Support\UnitKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A selection: the deck a user cuts out of a chapter's word list. It plays
 * like a chapter, it is listed like a chapter — and nobody else can see it.
 */
class SelectionsTest extends TestCase
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

    private function chapter(): Unit
    {
        return $this->german()->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();
    }

    /**
     * @return array<int, int>
     */
    private function someCards(int $count = 3): array
    {
        return $this->chapter()->cards()->take($count)->pluck('cards.id')->all();
    }

    private function make(string $name = 'Mots difficiles', ?array $cards = null): Unit
    {
        $this->post('/selections', [
            'unit' => $this->chapter()->id,
            'name' => $name,
            'cards' => $cards ?? $this->someCards(),
        ])->assertSessionHasNoErrors();

        return Unit::where('name', $name)->firstOrFail();
    }

    private function deckUrl(Unit $unit): string
    {
        return route('deck.show', [$this->german(), $unit->kind->slug(), $unit], absolute: false);
    }

    // --- making one -----------------------------------------------------------

    public function test_it_makes_a_deck_of_the_chosen_words_for_the_signed_in_user(): void
    {
        $cards = $this->someCards();

        $this->post('/selections', [
            'unit' => $this->chapter()->id,
            'name' => '  Mots difficiles  ',
            'cards' => $cards,
        ])->assertSessionHas('success');

        $selection = Unit::where('kind', UnitKind::Selection)->firstOrFail();

        $this->assertSame('Mots difficiles', $selection->name);
        $this->assertSame(User::where('username', 'anna')->value('id'), $selection->user_id);
        $this->assertSame($this->german()->id, $selection->language_id);
        $this->assertSame($cards, $selection->cards()->pluck('cards.id')->all());
    }

    public function test_it_copies_no_word_so_the_chapter_keeps_them_all(): void
    {
        $this->make();

        // The selection points at the chapter's cards: not one row was written.
        $this->assertSame(570, Card::count());
        $this->assertSame(146, $this->chapter()->cards()->count());
    }

    public function test_the_new_selection_is_played_by_the_same_game(): void
    {
        $selection = $this->make();
        $first = $selection->cards()->firstOrFail();

        $this->get($this->deckUrl($selection))
            ->assertOk()
            ->assertSee('Mots difficiles')
            ->assertSee('Je sais')
            ->assertSee('À revoir')
            ->assertSee('id="deck-data"', false)
            ->assertSee($first->term);
    }

    public function test_it_refuses_a_selection_without_a_name(): void
    {
        $this->post('/selections', ['unit' => $this->chapter()->id, 'cards' => $this->someCards()])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Unit::where('kind', UnitKind::Selection)->count());
    }

    public function test_it_refuses_a_selection_without_a_single_word(): void
    {
        $this->post('/selections', ['unit' => $this->chapter()->id, 'name' => 'Vide', 'cards' => []])
            ->assertSessionHasErrors('cards');

        $this->assertSame(0, Unit::where('kind', UnitKind::Selection)->count());
    }

    public function test_it_takes_only_the_words_that_are_on_the_list_it_was_opened_on(): void
    {
        $elsewhere = $this->german()->units()->where('kind', UnitKind::Verbs)->firstOrFail()->cards()->firstOrFail();
        $mine = $this->someCards(2);

        $this->post('/selections', [
            'unit' => $this->chapter()->id,
            'name' => 'Mélange',
            'cards' => [...$mine, $elsewhere->id],
        ])->assertSessionHasNoErrors();

        $this->assertSame($mine, Unit::where('name', 'Mélange')->firstOrFail()->cards()->pluck('cards.id')->all());
    }

    // --- adding to one that exists --------------------------------------------

    public function test_it_adds_words_to_a_selection_the_user_already_has(): void
    {
        $selection = $this->make(cards: $this->someCards(2));
        $more = $this->chapter()->cards()->skip(2)->take(2)->pluck('cards.id')->all();

        $this->post('/selections', [
            'unit' => $this->chapter()->id,
            'selection' => $selection->id,
            'cards' => $more,
        ])->assertRedirect($this->deckUrl($selection));

        $this->assertSame(4, $selection->cards()->count());
        $this->assertSame([...$this->someCards(2), ...$more], $selection->cards()->pluck('cards.id')->all());
        $this->assertSame(1, Unit::where('kind', UnitKind::Selection)->count());
    }

    public function test_a_word_already_in_the_selection_is_not_added_twice(): void
    {
        $selection = $this->make(cards: $this->someCards(2));

        $this->post('/selections', [
            'unit' => $this->chapter()->id,
            'selection' => $selection->id,
            'cards' => $this->someCards(3),
        ])->assertSessionHas('success');

        $this->assertSame(3, $selection->cards()->count());
    }

    public function test_it_refuses_to_add_to_someone_elses_selection(): void
    {
        $selection = $this->make();

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);

        $this->post('/selections', [
            'unit' => $this->chapter()->id,
            'selection' => $selection->id,
            'cards' => $this->someCards(),
        ])->assertNotFound();

        $this->assertSame(3, $selection->cards()->count());
    }

    // --- whose it is ----------------------------------------------------------

    public function test_only_its_owner_can_open_it(): void
    {
        $selection = $this->make();

        $this->get($this->deckUrl($selection))->assertOk();

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);

        $this->get($this->deckUrl($selection))->assertNotFound();
    }

    public function test_the_language_page_lists_the_users_own_selections_only(): void
    {
        $this->make();

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('Mes sélections')
            ->assertSee('Mots difficiles');

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);

        $this->get('/allemand')
            ->assertOk()
            ->assertDontSee('Mes sélections')
            ->assertDontSee('Mots difficiles');
    }

    public function test_the_rail_lists_the_users_own_selections_only(): void
    {
        $selection = $this->make();
        $this->make('Verbes durs', $this->someCards(2));

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);
        $mine = $this->make('À moi');

        $this->get($this->deckUrl($mine))
            ->assertOk()
            ->assertSee('À moi')
            ->assertDontSee($selection->name)
            ->assertDontSee('Verbes durs');
    }

    public function test_a_selection_does_not_show_up_among_the_chapters(): void
    {
        $selection = $this->make();

        // It is offered in the picker — that is the point of it — but the rail
        // beside a chapter lists chapters, and nothing else.
        $this->get($this->deckUrl($this->chapter()))
            ->assertOk()
            ->assertDontSee('data-rename-url="'.route('units.update', $selection).'"', false)
            ->assertDontSee('href="'.$this->deckUrl($selection).'"', false);
    }

    // --- what the user knows --------------------------------------------------

    public function test_a_word_marked_in_a_selection_is_known_in_its_chapter_too(): void
    {
        $selection = $this->make();
        $card = $selection->cards()->firstOrFail();

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertOk();

        // One mark, one row, counted on both lists.
        $this->assertSame(1, CardState::count());

        $known = CardState::knownPerUnit(auth()->id(), [$selection->id, $this->chapter()->id]);

        $this->assertSame(1, $known[$selection->id]);
        $this->assertSame(1, $known[$this->chapter()->id]);

        $this->get($this->deckUrl($selection))->assertOk()->assertSee('1 / 3');
        $this->get($this->deckUrl($this->chapter()))->assertOk()->assertSee('1 / 146');
    }

    public function test_a_selection_does_not_inflate_the_language_totals(): void
    {
        // The picker counts the cards a language wrote, so the same word met
        // twice is still one card.
        $this->get('/')->assertOk()->assertSee('447');

        $this->make();

        $this->get('/')->assertOk()->assertSee('447');
    }

    // --- renaming and deleting -------------------------------------------------

    public function test_its_owner_renames_it_from_the_rail(): void
    {
        $selection = $this->make();

        $this->patchJson("/units/{$selection->id}", ['rename' => 'Les pires'])
            ->assertOk()
            ->assertExactJson(['id' => $selection->id, 'name' => 'Les pires']);

        $this->assertSame('Les pires', $selection->fresh()->name);
    }

    public function test_nobody_else_can_rename_it(): void
    {
        $selection = $this->make();

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);

        $this->patchJson("/units/{$selection->id}", ['rename' => 'Volé'])->assertNotFound();

        $this->assertSame('Mots difficiles', $selection->fresh()->name);
    }

    public function test_its_owner_deletes_it_and_the_words_stay_in_the_chapter(): void
    {
        $selection = $this->make();

        $this->delete("/selections/{$selection->id}")
            ->assertRedirect(route('program.show', $this->german(), absolute: false))
            ->assertSessionHas('success');

        $this->assertNull(Unit::find($selection->id));
        $this->assertSame(146, $this->chapter()->cards()->count());
        $this->assertSame(570, Card::count());
    }

    public function test_nobody_else_can_delete_it(): void
    {
        $selection = $this->make();

        $this->post('/logout');
        $this->post('/login', ['username' => 'ben']);

        $this->delete("/selections/{$selection->id}")->assertNotFound();

        $this->assertNotNull(Unit::find($selection->id));
    }

    public function test_a_chapter_is_not_deleted_through_the_selection_route(): void
    {
        $chapter = $this->chapter();

        $this->delete("/selections/{$chapter->id}")->assertNotFound();

        $this->assertNotNull(Unit::find($chapter->id));
    }

    public function test_an_administrator_does_not_delete_a_selection_as_a_unit(): void
    {
        $selection = $this->make();

        config(['admin.password' => 'ouvre-toi']);
        $this->post('/admin', ['password' => 'ouvre-toi'])->assertRedirect('/');

        $this->delete("/units/{$selection->id}")->assertNotFound();

        $this->assertNotNull(Unit::find($selection->id));
    }

    // --- the picker on the word list -------------------------------------------

    public function test_the_word_list_carries_a_tick_per_line_and_the_bar_under_it(): void
    {
        $this->get($this->deckUrl($this->chapter()))
            ->assertOk()
            ->assertSee('data-pick', false)
            ->assertSee('action="'.route('selections.store').'"', false)
            ->assertSee('id="pickCount"', false)
            ->assertSee('data-pick-clear', false)
            ->assertSee('Créer la sélection');
    }

    public function test_the_bar_offers_the_selections_the_user_already_has(): void
    {
        $this->make();

        $this->get($this->deckUrl($this->chapter()))
            ->assertOk()
            ->assertSee('data-pick-target', false)
            ->assertSee('Mots difficiles');
    }

    public function test_a_selection_is_not_itself_a_list_to_cut_from(): void
    {
        $selection = $this->make();

        $this->get($this->deckUrl($selection))
            ->assertOk()
            ->assertDontSee('data-pick', false)
            ->assertDontSee('Créer la sélection');
    }

    // --- access -----------------------------------------------------------------

    public function test_guests_cannot_reach_the_selection_routes(): void
    {
        $selection = $this->make();
        $this->post('/logout');

        $this->post('/selections', ['unit' => $this->chapter()->id, 'name' => 'X', 'cards' => $this->someCards()])
            ->assertRedirect('/login');

        $this->delete("/selections/{$selection->id}")->assertRedirect('/login');

        $this->assertSame(1, Unit::where('kind', UnitKind::Selection)->count());
    }
}
