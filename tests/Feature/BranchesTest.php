<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * There is no branch navigation on screen any more, but chapters still belong to
 * a branch in the database and that grouping still orders the chapter list.
 */
class BranchesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->post('/login', ['username' => 'anna']);
    }

    public function test_it_seeds_the_nine_subjects_in_order(): void
    {
        $this->assertSame(
            ['Algèbre', 'Géométrie', 'Physique', 'Chimie', 'Économie', 'Histoire', 'Français', 'Allemand', 'Anglais'],
            Branch::orderBy('position')->pluck('name')->all(),
        );
    }

    public function test_the_sidebar_no_longer_shows_branches(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Branches')
            ->assertDontSee('aria-label="Branches"', false)
            ->assertDontSee('Géométrie');
    }

    public function test_the_sidebar_lists_every_chapter_whatever_its_branch(): void
    {
        $chimie = Branch::where('slug', 'chimie')->firstOrFail();
        Chapter::create(['branch_id' => $chimie->id, 'name' => 'Les acides', 'position' => 1]);

        $response = $this->get('/')->assertOk()->assertSee('Chapitres');

        // Chemistry and German chapters are both reachable from the one list.
        foreach (Chapter::all() as $chapter) {
            $response->assertSee($chapter->name);
        }
    }

    public function test_chapters_are_listed_in_branch_order(): void
    {
        $chimie = Branch::where('slug', 'chimie')->firstOrFail();   // position 4
        Chapter::create(['branch_id' => $chimie->id, 'name' => 'Les acides', 'position' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        // Chimie (4) comes before Allemand (8).
        $this->assertLessThan(
            strpos($html, 'I. Der Mensch'),
            strpos($html, 'Les acides'),
        );
    }

    public function test_a_first_visit_opens_the_first_chapter(): void
    {
        $this->get('/')->assertOk()->assertSee('der Mensch, en, en');

        $german = Chapter::whereHas('branch', fn ($q) => $q->where('slug', 'allemand'))->firstOrFail();
        $this->assertSame($german->id, session('chapter_id'));
    }

    public function test_breadcrumbs_name_the_chapter(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('I. Der Mensch — Personalien &amp; Familie', false)
            ->assertDontSee('Allemand');
    }

    public function test_a_new_chapter_joins_the_branch_of_the_one_on_screen(): void
    {
        $german = Chapter::whereHas('branch', fn ($q) => $q->where('slug', 'allemand'))->firstOrFail();
        $this->get("/chapters/{$german->id}");

        $this->post('/chapters', [
            'name' => 'II. Die Wohnung',
            'words' => '{"la maison": "das Haus, ¨er"}',
        ])->assertRedirect('/');

        $created = Chapter::where('name', 'II. Die Wohnung')->firstOrFail();
        $this->assertSame($german->branch_id, $created->branch_id);
        $this->assertSame(2, $created->position);
    }

    public function test_chapter_positions_are_numbered_per_branch(): void
    {
        $chimie = Branch::where('slug', 'chimie')->firstOrFail();
        Chapter::create(['branch_id' => $chimie->id, 'name' => 'Les acides', 'position' => 1]);
        Chapter::create(['branch_id' => $chimie->id, 'name' => 'Les bases', 'position' => 2]);

        $this->assertSame([1, 2], $chimie->chapters()->pluck('position')->all());

        $allemand = Branch::where('slug', 'allemand')->firstOrFail();
        $this->assertSame([1], $allemand->chapters()->pluck('position')->all());
    }

    public function test_opening_a_chapter_selects_it(): void
    {
        $german = Chapter::whereHas('branch', fn ($q) => $q->where('slug', 'allemand'))->firstOrFail();

        $this->get("/chapters/{$german->id}")->assertRedirect('/');
        $this->assertSame($german->id, session('chapter_id'));
    }
}
