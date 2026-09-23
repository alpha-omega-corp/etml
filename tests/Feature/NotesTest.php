<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * History: a subject read from revision notes rather than drilled as cards.
 */
class NotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->post('/login', ['username' => 'anna']);
    }

    private function note(): Note
    {
        return Language::where('slug', 'histoire')->firstOrFail()->notes()->firstOrFail();
    }

    public function test_the_seed_ships_history_with_its_note(): void
    {
        $note = $this->note();

        $this->assertSame('Suisse et industrialisation', $note->title);
        $this->assertSame(['Histoire suisse', 'Industrialisation'], $note->subjects);
        $this->assertStringContainsString('<h1>Suisse et industrialisation</h1>', $note->html);

        // Re-running updates the note in place rather than doubling it.
        $this->seed();
        $this->assertSame(1, Note::count());
    }

    public function test_history_lists_its_notes_and_no_empty_decks(): void
    {
        $this->get('/')->assertOk()->assertSee('Histoire')->assertSee('fiche');

        $this->get('/histoire')
            ->assertOk()
            ->assertSee('Fiches')
            ->assertSee('Suisse et industrialisation')
            ->assertSee('Histoire suisse · Industrialisation')
            ->assertSee(route('notes.show', $this->note()), false)
            ->assertDontSee('Vocabulaire')
            ->assertDontSee('Verbes');

        // A language without notes keeps its decks.
        $this->get('/allemand')->assertSee('Vocabulaire')->assertDontSee('id="notes"', false);
    }

    public function test_a_note_is_framed_under_a_trail_back_to_its_subject(): void
    {
        $note = $this->note();

        $this->get(route('notes.show', $note))
            ->assertOk()
            ->assertSee(route('program.show', $note->language), false)
            ->assertSee('<iframe class="note__page" data-note-frame src="'.route('notes.page', $note).'"', false);
    }

    public function test_the_page_is_served_sandboxed_with_the_theme_bridge_in_its_head(): void
    {
        $note = $this->note();

        $response = $this->get(route('notes.page', $note))->assertOk();
        $html = $response->getContent();

        $response->assertHeader('Content-Security-Policy', 'sandbox allow-scripts allow-popups');

        // The bridge goes after the note's own styles, so its mapping wins,
        // and the note is otherwise untouched.
        $bridge = strpos($html, ':root[data-app-theme]');
        $this->assertGreaterThan(strpos($html, '<style>'), $bridge);
        $this->assertLessThan(strpos($html, '</head>'), $bridge);
        $this->assertSame($note->html, str_replace(view('notes.theme')->render(), '', $html));
    }

    public function test_notes_need_a_signed_in_user(): void
    {
        $this->post('/logout');

        $this->get(route('notes.page', $this->note()))->assertRedirect('/login');
    }
}
