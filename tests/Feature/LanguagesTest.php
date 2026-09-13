<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\CardState;
use App\Models\Language;
use App\Models\ProgramEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The front door: choosing what to learn.
 */
class LanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->post('/login', ['username' => 'anna']);
    }

    public function test_it_seeds_the_languages_in_order(): void
    {
        $this->assertSame(
            ['Allemand', 'Anglais'],
            Language::orderBy('position')->pluck('name')->all(),
        );

        $german = Language::where('slug', 'allemand')->firstOrFail();
        $this->assertSame('de', $german->code);
        $this->assertSame('Deutsch', $german->label);
    }

    public function test_the_home_page_offers_every_language(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Que voulez-vous apprendre')
            ->assertSee('Allemand')
            ->assertSee('Deutsch')
            ->assertSee('Anglais')
            ->assertSee(route('program.show', 'allemand'), false)
            ->assertSee(route('program.show', 'anglais'), false);
    }

    public function test_it_sends_guests_to_the_login_page(): void
    {
        $this->post('/logout');

        $this->get('/')->assertRedirect('/login');
    }

    public function test_it_creates_the_user_on_first_sign_in_and_reuses_it_afterwards(): void
    {
        $this->post('/logout');

        $this->post('/login', ['username' => 'bea'])->assertRedirect('/');
        $this->assertSame(1, User::where('username', 'bea')->count());

        $this->post('/logout');
        $this->post('/login', ['username' => 'bea'])->assertRedirect('/');
        $this->assertSame(1, User::where('username', 'bea')->count());
    }

    public function test_it_rejects_an_empty_username(): void
    {
        $this->post('/logout');

        $this->post('/login', ['username' => ''])->assertSessionHasErrors('username');
    }

    public function test_each_language_shows_its_size_and_what_the_user_knows(): void
    {
        $card = Card::orderBy('position')->firstOrFail();
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertOk();

        $html = $this->get('/')->assertOk()->getContent();

        // 146 + 179 + 61 + 61 seeded cards, one of them known.
        $this->assertStringContainsString('>447<', $html);
        $this->assertStringContainsString('>1<', $html);
        $this->assertStringContainsString('cartes', $html);
        $this->assertStringContainsString('connues', $html);
    }

    public function test_the_tile_names_the_next_date_on_the_programme(): void
    {
        $german = Language::where('slug', 'allemand')->firstOrFail();

        ProgramEntry::create([
            'language_id' => $german->id,
            'date' => Carbon::today()->addDays(3)->toDateString(),
            'title' => 'Test 1',
        ]);

        // A date already gone must not be the one advertised.
        ProgramEntry::create([
            'language_id' => $german->id,
            'date' => Carbon::today()->subWeek()->toDateString(),
            'title' => 'Test 0',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Test 1')
            ->assertDontSee('Test 0')
            ->assertSee('Aucune date au programme');  // English has none
    }

    public function test_progress_is_counted_per_language(): void
    {
        $english = Language::where('slug', 'anglais')->firstOrFail();
        $card = $english->cards()->firstOrFail();

        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertOk();

        $this->get('/')->assertOk()->assertSee('Anglais');

        // The mark counts for English and leaves German at nothing.
        $german = Language::where('slug', 'allemand')->firstOrFail();
        $known = CardState::knownPerLanguage(auth()->id());

        $this->assertSame(1, $known[$english->id]);
        $this->assertArrayNotHasKey($german->id, $known);
    }
}
