<?php

namespace Tests\Unit;

use App\Support\ProgramList;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ProgramListTest extends TestCase
{
    public function test_it_reads_a_dated_entry_with_both_kinds_of_unit(): void
    {
        $rows = ProgramList::parse('[{"date": "2026-09-22", "test": "Test 1", "chapters": ["I. Der Mensch"], "pages": ["Verbes p. 12"], "note": "à réviser"}]');

        $this->assertCount(1, $rows);
        $this->assertSame('2026-09-22', $rows[0]['date']);
        $this->assertSame('Test 1', $rows[0]['title']);
        $this->assertSame('à réviser', $rows[0]['note']);
        $this->assertSame(['I. Der Mensch'], $rows[0]['chapters']);
        $this->assertSame(['Verbes p. 12'], $rows[0]['pages']);
    }

    public function test_only_the_date_is_required(): void
    {
        $rows = ProgramList::parse('[{"date": "2026-09-22"}]');

        $this->assertNull($rows[0]['title']);
        $this->assertNull($rows[0]['note']);
        $this->assertSame([], $rows[0]['chapters']);
        $this->assertSame([], $rows[0]['pages']);
    }

    public function test_it_sorts_by_date(): void
    {
        $rows = ProgramList::parse('[{"date": "2026-10-06"}, {"date": "2026-09-22"}]');

        $this->assertSame(['2026-09-22', '2026-10-06'], array_column($rows, 'date'));
    }

    public function test_it_accepts_french_keys_and_a_bare_name(): void
    {
        $rows = ProgramList::parse('[{"date": "2026-09-22", "titre": "Contrôle", "chapitres": "I. Der Mensch", "verbes": "p. 12"}]');

        $this->assertSame('Contrôle', $rows[0]['title']);
        $this->assertSame(['I. Der Mensch'], $rows[0]['chapters']);
        $this->assertSame(['p. 12'], $rows[0]['pages']);
    }

    public function test_it_trims_names_and_drops_blanks_and_repeats(): void
    {
        $rows = ProgramList::parse('[{"date": "2026-09-22", "chapters": ["  I. Der Mensch  ", "", "I. Der Mensch"]}]');

        $this->assertSame(['I. Der Mensch'], $rows[0]['chapters']);
    }

    public function test_it_refuses_an_entry_without_a_date(): void
    {
        $this->expectException(RuntimeException::class);

        ProgramList::parse('[{"test": "Test 1"}]');
    }

    public function test_it_refuses_a_date_that_does_not_exist(): void
    {
        $this->expectException(RuntimeException::class);

        ProgramList::parse('[{"date": "2026-02-31"}]');
    }

    public function test_it_refuses_a_date_in_another_format(): void
    {
        $this->expectException(RuntimeException::class);

        ProgramList::parse('[{"date": "22/09/2026"}]');
    }

    public function test_it_refuses_something_that_is_not_a_list(): void
    {
        $this->expectException(RuntimeException::class);

        ProgramList::parse('{"date": "2026-09-22"}');
    }

    public function test_it_refuses_a_unit_list_that_is_not_made_of_names(): void
    {
        $this->expectException(RuntimeException::class);

        ProgramList::parse('[{"date": "2026-09-22", "chapters": [{"name": "I"}]}]');
    }

    public function test_it_refuses_more_than_the_cap(): void
    {
        $rows = array_fill(0, ProgramList::MAX_ENTRIES + 1, ['date' => '2026-09-22']);

        $this->expectException(RuntimeException::class);

        ProgramList::parse(json_encode($rows));
    }

    public function test_it_refuses_an_empty_paste(): void
    {
        $this->expectException(RuntimeException::class);

        ProgramList::parse('   ');
    }
}
