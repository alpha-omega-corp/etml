<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Card;
use App\Models\CardState;
use App\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
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
        $this->post('/admin', ['password' => 'ouvre-toi'])->assertRedirect('/');
    }

    private function german(): Chapter
    {
        return Branch::where('slug', 'allemand')->firstOrFail()->chapters()->firstOrFail();
    }

    // --- entering the mode ----------------------------------------------------

    public function test_a_signed_in_user_is_not_an_administrator(): void
    {
        $this->assertNull(session('is_admin'));
        $this->get('/')->assertOk()->assertSee('Mode administrateur')->assertDontSee('Quitter le mode admin');
    }

    public function test_the_right_password_enters_the_mode(): void
    {
        $this->beAdmin();

        $this->assertTrue(session('is_admin'));
        $this->get('/')->assertOk()->assertSee('Quitter le mode admin');
    }

    public function test_a_wrong_password_does_not(): void
    {
        $this->post('/admin', ['password' => 'devine'])->assertSessionHasErrors('password');
        $this->assertNull(session('is_admin'));
    }

    public function test_an_empty_password_is_rejected(): void
    {
        $this->post('/admin', ['password' => ''])->assertSessionHasErrors('password');
        $this->assertNull(session('is_admin'));
    }

    public function test_a_bcrypt_hash_in_the_config_works_too(): void
    {
        config(['admin.password' => Hash::make('ouvre-toi')]);

        $this->post('/admin', ['password' => 'ouvre-toi'])->assertRedirect('/');
        $this->assertTrue(session('is_admin'));
    }

    public function test_the_mode_cannot_be_entered_when_no_password_is_configured(): void
    {
        config(['admin.password' => null]);

        // An empty configured password must never mean "anything unlocks it".
        $this->post('/admin', ['password' => ''])->assertSessionHasErrors('password');
        $this->post('/admin', ['password' => 'nimporte'])->assertSessionHasErrors('password');

        $this->assertNull(session('is_admin'));
    }

    public function test_the_login_page_says_when_it_is_not_configured(): void
    {
        config(['admin.password' => null]);

        $this->get('/admin')->assertOk()->assertSee('ADMIN_PASSWORD');
    }

    public function test_leaving_the_mode(): void
    {
        $this->beAdmin();

        $this->post('/admin/logout')->assertRedirect('/');
        $this->assertNull(session('is_admin'));
    }

    public function test_signing_out_drops_the_mode(): void
    {
        $this->beAdmin();

        $this->post('/logout');
        $this->post('/login', ['username' => 'anna']);

        $this->assertNull(session('is_admin'));
    }

    public function test_guests_cannot_reach_the_admin_login(): void
    {
        $this->post('/logout');

        $this->get('/admin')->assertRedirect('/login');
        $this->post('/admin', ['password' => 'ouvre-toi'])->assertRedirect('/login');
    }

    // --- deleting chapters ----------------------------------------------------

    public function test_the_delete_control_is_only_rendered_for_an_administrator(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Supprimer «', false);

        $this->beAdmin();

        $this->get('/')->assertOk()->assertSee('Supprimer «', false);
    }

    public function test_an_administrator_deletes_a_chapter_and_everything_under_it(): void
    {
        $chapter = $this->german();
        $card = $chapter->cards()->firstOrFail();
        $this->postJson("/cards/{$card->id}/status", ['status' => 'known'])->assertOk();
        $this->assertSame(1, CardState::count());

        $this->beAdmin();

        $this->delete("/chapters/{$chapter->id}")
            ->assertRedirect('/')
            ->assertSessionHas('success');

        $this->assertNull(Chapter::find($chapter->id));
        $this->assertSame(0, Card::where('chapter_id', $chapter->id)->count());
        $this->assertSame(0, CardState::count());
    }

    public function test_deleting_the_open_chapter_clears_the_selection(): void
    {
        $chapter = $this->german();
        $this->get("/chapters/{$chapter->id}");
        $this->assertSame($chapter->id, session('chapter_id'));

        $this->beAdmin();
        $this->delete("/chapters/{$chapter->id}");

        $this->assertNotSame($chapter->id, session('chapter_id'));
        $this->get('/')->assertOk();
    }

    public function test_a_plain_user_cannot_delete_a_chapter(): void
    {
        $chapter = $this->german();

        $this->delete("/chapters/{$chapter->id}")->assertForbidden();

        $this->assertNotNull(Chapter::find($chapter->id));
    }

    public function test_a_guest_cannot_delete_a_chapter(): void
    {
        $chapter = $this->german();
        $this->post('/logout');

        $this->delete("/chapters/{$chapter->id}")->assertRedirect('/login');

        $this->assertNotNull(Chapter::find($chapter->id));
    }

    // --- staying signed in ----------------------------------------------------

    public function test_the_session_lifetime_is_long(): void
    {
        // A month, so the deck is still there after a break from studying.
        $this->assertGreaterThanOrEqual(43200, config('session.lifetime'));
        $this->assertFalse((bool) config('session.expire_on_close'));
    }
}
