<?php

namespace App\Support;

use App\Models\Language;
use App\Models\ProgramEntry;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * Writes a parsed programme onto a language, replacing whatever was there.
 *
 * Shared by the paste form and the seeder, so a programme loaded from a file
 * and one pasted in the browser land in the database the same way.
 */
class ProgramImporter
{
    /**
     * @param  array<int, array{date: string, title: string|null, note: string|null, chapters: array<int, string>, pages: array<int, string>}>  $rows
     */
    public static function apply(Language $language, array $rows): void
    {
        DB::transaction(function () use ($language, $rows) {
            $language->programEntries()->delete();

            foreach ($rows as $row) {
                $entry = ProgramEntry::create([
                    'language_id' => $language->id,
                    'date' => $row['date'],
                    'title' => $row['title'],
                    'note' => $row['note'],
                ]);

                $entry->units()->sync([
                    ...self::unitIds($language, UnitKind::Vocabulary, $row['chapters']),
                    ...self::unitIds($language, UnitKind::Verbs, $row['pages']),
                ]);
            }
        });
    }

    /**
     * Resolve unit names to ids, creating the ones that do not exist yet. A
     * programme names what has to be learnt before the word lists are typed
     * in, so an unknown name is a new empty unit rather than an error.
     *
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    private static function unitIds(Language $language, UnitKind $kind, array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            $unit = Unit::where('language_id', $language->id)
                ->where('kind', $kind)
                ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                ->first();

            $ids[] = ($unit ?? Unit::create([
                'language_id' => $language->id,
                'kind' => $kind,
                'name' => $name,
                'position' => Unit::nextPosition($language, $kind),
            ]))->id;
        }

        return $ids;
    }
}
