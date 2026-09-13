<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\ProgramEntry;
use App\Models\Unit;
use App\Support\UnitKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adding, correcting and removing one line of a programme by hand.
 */
class ProgramEntryTest extends TestCase
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

    private function chapter(): Unit
    {
        return $this->german()->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();
    }

    private function verbs(): Unit
    {
        // The shipped verb page already occupies position 1.
        return $this->german()->units()->where('kind', UnitKind::Verbs)->firstOrFail();
    }

    private function entry(array $attributes = []): ProgramEntry
    {
        return ProgramEntry::create($attributes + [
            'language_id' => $this->german()->id,
            'date' => '2026-09-22',
            'title' => 'Test 1',
        ]);
    }

    // --- adding ---------------------------------------------------------------

    public function test_the_page_offers_an_add_button_and_the_form_behind_it(): void
    {
        $this->beAdmin();

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('Ajouter une date')
            ->assertSee('data-modal-open="entry"', false)
            ->assertSee('data-entry-new', false)
            ->assertSee('id="entry-form"', false)
            ->assertSee(route('entries.save'), false)
            // Every unit of the language is offered as something to learn.
            ->assertSee('name="units[]"', false)
            ->assertSee($this->chapter()->name);
    }

    public function test_an_administrator_adds_a_date(): void
    {
        $this->beAdmin();
        $chapter = $this->chapter();

        $this->post(route('entries.save'), [
            'language' => 'allemand',
            'date' => '2026-11-20',
            'title' => 'Test 3',
            'note' => 'Oral',
            'units' => [$chapter->id],
        ])->assertRedirect('/allemand')->assertSessionHas('success');

        $entry = ProgramEntry::firstOrFail();

        $this->assertSame('2026-11-20', $entry->date->toDateString());
        $this->assertSame('Test 3', $entry->title);
        $this->assertSame('Oral', $entry->note);
        $this->assertSame([$chapter->id], $entry->units->pluck('id')->all());

        $this->get('/allemand')->assertOk()->assertSee('Test 3')->assertSee('Oral');
    }

    public function test_a_date_needs_nothing_but_its_date(): void
    {
        $this->beAdmin();

        $this->post(route('entries.save'), ['language' => 'allemand', 'date' => '2026-11-20'])
            ->assertSessionHas('success');

        $entry = ProgramEntry::firstOrFail();

        $this->assertNull($entry->title);
        $this->assertNull($entry->note);
        $this->assertCount(0, $entry->units);

        // With no title the line falls back to the default wording.
        $this->get('/allemand')->assertOk()->assertSee('À apprendre');
    }

    public function test_a_blank_title_is_stored_as_no_title(): void
    {
        $this->beAdmin();

        $this->post(route('entries.save'), ['language' => 'allemand', 'date' => '2026-11-20', 'title' => '   ']);

        $this->assertNull(ProgramEntry::firstOrFail()->title);
    }

    public function test_it_can_point_at_a_chapter_and_a_verb_page_at_once(): void
    {
        $this->beAdmin();
        $verbs = $this->verbs();

        $this->post(route('entries.save'), [
            'language' => 'allemand',
            'date' => '2026-11-20',
            'units' => [$this->chapter()->id, $verbs->id],
        ])->assertSessionHas('success');

        $this->assertCount(2, ProgramEntry::firstOrFail()->units);
    }

    // --- editing --------------------------------------------------------------

    public function test_each_line_carries_its_values_and_an_edit_control(): void
    {
        $this->beAdmin();
        $entry = $this->entry(['note' => 'Oral']);
        $entry->units()->sync([$this->chapter()->id]);

        $this->get('/allemand')
            ->assertOk()
            ->assertSee('data-entry-id="'.$entry->id.'"', false)
            ->assertSee('data-entry-date="2026-09-22"', false)
            ->assertSee('data-entry-title="Test 1"', false)
            ->assertSee('data-entry-note="Oral"', false)
            ->assertSee('data-entry-units="'.$this->chapter()->id.'"', false)
            ->assertSee('data-entry-edit', false)
            ->assertSee('Modifier « Test 1 »', false);
    }

    public function test_an_administrator_edits_a_date_in_place(): void
    {
        $this->beAdmin();
        $entry = $this->entry();
        $chapter = $this->chapter();

        $this->post(route('entries.save'), [
            'language' => 'allemand',
            'entry_id' => $entry->id,
            'date' => '2026-09-29',
            'title' => 'Test 1 (reporté)',
            'units' => [$chapter->id],
        ])->assertRedirect('/allemand')->assertSessionHas('success');

        $entry->refresh();

        // Edited, not duplicated.
        $this->assertSame(1, ProgramEntry::count());
        $this->assertSame('2026-09-29', $entry->date->toDateString());
        $this->assertSame('Test 1 (reporté)', $entry->title);
        $this->assertSame([$chapter->id], $entry->units->pluck('id')->all());
    }

    public function test_editing_can_clear_what_a_date_covers(): void
    {
        $this->beAdmin();
        $entry = $this->entry();
        $entry->units()->sync([$this->chapter()->id]);

        $this->post(route('entries.save'), [
            'language' => 'allemand',
            'entry_id' => $entry->id,
            'date' => '2026-09-22',
        ]);

        $this->assertCount(0, $entry->fresh()->units);
    }

    // --- removing ---------------------------------------------------------------

    public function test_an_administrator_removes_a_date(): void
    {
        $this->beAdmin();
        $entry = $this->entry();
        $entry->units()->sync([$this->chapter()->id]);

        $this->delete(route('entries.destroy', $entry))
            ->assertRedirect('/allemand')
            ->assertSessionHas('success');

        $this->assertSame(0, ProgramEntry::count());
        // The chapter itself is not a casualty of dropping the date.
        $this->assertNotNull(Unit::find($this->chapter()->id));
    }

    // --- what is refused ----------------------------------------------------------

    public function test_it_refuses_a_missing_or_malformed_date(): void
    {
        $this->beAdmin();

        $this->post(route('entries.save'), ['language' => 'allemand'])->assertSessionHasErrors('date');
        $this->post(route('entries.save'), ['language' => 'allemand', 'date' => '22/09/2026'])->assertSessionHasErrors('date');

        $this->assertSame(0, ProgramEntry::count());
    }

    public function test_it_refuses_a_unit_from_another_language(): void
    {
        $this->beAdmin();

        $english = Language::where('slug', 'anglais')->firstOrFail();
        $foreign = $english->units()->where('kind', UnitKind::Vocabulary)->firstOrFail();

        $this->post(route('entries.save'), [
            'language' => 'allemand',
            'date' => '2026-11-20',
            'units' => [$foreign->id],
        ])->assertSessionHasErrors('units.0');

        $this->assertSame(0, ProgramEntry::count());
    }

    public function test_it_refuses_an_entry_belonging_to_another_language(): void
    {
        $this->beAdmin();

        $english = Language::where('slug', 'anglais')->firstOrFail();
        $foreign = ProgramEntry::create(['language_id' => $english->id, 'date' => '2026-11-20', 'title' => 'Anglais']);

        $this->post(route('entries.save'), [
            'language' => 'allemand',
            'entry_id' => $foreign->id,
            'date' => '2026-12-01',
        ])->assertSessionHasErrors('entry_id');

        $this->assertSame('Anglais', $foreign->fresh()->title);
    }

    public function test_a_refused_save_reopens_the_form_on_what_was_typed(): void
    {
        $this->beAdmin();
        $entry = $this->entry();

        $this->from('/allemand')
            ->post(route('entries.save'), ['language' => 'allemand', 'entry_id' => $entry->id, 'date' => 'demain'])
            ->assertRedirect('/allemand');

        $this->followingRedirects()
            ->from('/allemand')
            ->post(route('entries.save'), ['language' => 'allemand', 'entry_id' => $entry->id, 'date' => 'demain'])
            ->assertOk()
            ->assertSee('data-entry-reopen', false)
            ->assertSee('La date doit être au format 2026-09-22.')
            ->assertSee('value="'.$entry->id.'"', false);
    }

    // --- access -------------------------------------------------------------------

    public function test_a_plain_user_sees_no_controls_and_cannot_use_the_routes(): void
    {
        $entry = $this->entry();

        $this->get('/allemand')
            ->assertOk()
            ->assertDontSee('Ajouter une date')
            ->assertDontSee('data-entry-edit', false)
            ->assertDontSee('id="entry-form"', false);

        $this->post(route('entries.save'), ['language' => 'allemand', 'date' => '2026-11-20'])->assertForbidden();
        $this->delete(route('entries.destroy', $entry))->assertForbidden();

        $this->assertSame(1, ProgramEntry::count());
    }

    public function test_guests_cannot_use_the_routes(): void
    {
        $entry = $this->entry();
        $this->post('/logout');

        $this->post(route('entries.save'), ['language' => 'allemand', 'date' => '2026-11-20'])->assertRedirect('/login');
        $this->delete(route('entries.destroy', $entry))->assertRedirect('/login');

        $this->assertSame(1, ProgramEntry::count());
    }
}
