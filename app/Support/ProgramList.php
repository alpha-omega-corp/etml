<?php

namespace App\Support;

use JsonException;
use RuntimeException;

/**
 * Reads a pasted programme: the dated tests of one language and, for each, the
 * chapters and verb pages it covers.
 *
 *     [
 *       {"date": "2026-09-22", "test": "Test 1",
 *        "chapters": ["I. Der Mensch"], "pages": ["Verbes p. 12"]},
 *       {"date": "2026-10-06", "test": "Test 2", "chapters": ["II. Die Wohnung"]}
 *     ]
 *
 * Only the date is required. Units are named, not numbered: a name that has no
 * unit yet is created empty, so a programme can be pasted before the word
 * lists arrive.
 */
class ProgramList
{
    public const MAX_ENTRIES = 200;

    private const MAX_UNITS = 20;

    /**
     * @return array<int, array{date: string, title: string|null, note: string|null, chapters: array<int, string>, pages: array<int, string>}>
     *
     * @throws RuntimeException with a message meant for the user
     */
    public static function parse(string $json): array
    {
        $json = trim($json);

        if ($json === '') {
            throw new RuntimeException('Collez le programme au format JSON.');
        }

        try {
            $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('JSON invalide : '.$e->getMessage());
        }

        if (! is_array($decoded) || ! array_is_list($decoded) || $decoded === []) {
            throw new RuntimeException('Le programme doit être une liste d\'entrées datées.');
        }

        if (count($decoded) > self::MAX_ENTRIES) {
            throw new RuntimeException(self::MAX_ENTRIES.' dates au maximum (reçu '.count($decoded).').');
        }

        $rows = array_map(self::row(...), $decoded, array_keys($decoded));

        usort($rows, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $rows;
    }

    /**
     * @param  mixed  $entry  one decoded element of the list
     * @return array{date: string, title: string|null, note: string|null, chapters: array<int, string>, pages: array<int, string>}
     */
    private static function row(mixed $entry, int $i): array
    {
        $line = $i + 1;

        if (! is_array($entry)) {
            throw new RuntimeException("Entrée {$line} : un objet {\"date\": …} est attendu.");
        }

        $date = $entry['date'] ?? null;

        if (! is_string($date) || ! self::isDate($date)) {
            throw new RuntimeException("Entrée {$line} : « date » est obligatoire, au format 2026-09-22.");
        }

        return [
            'date' => $date,
            'title' => self::text($entry['test'] ?? $entry['title'] ?? $entry['titre'] ?? null),
            'note' => self::text($entry['note'] ?? $entry['remarque'] ?? null),
            'chapters' => self::names($entry['chapters'] ?? $entry['chapitres'] ?? null, $line),
            'pages' => self::names($entry['pages'] ?? $entry['verbes'] ?? $entry['verbs'] ?? null, $line),
        ];
    }

    private static function isDate(string $value): bool
    {
        $parsed = date_parse_from_format('Y-m-d', $value);

        return $parsed['error_count'] === 0
            && $parsed['warning_count'] === 0
            && checkdate((int) $parsed['month'], (int) $parsed['day'], (int) $parsed['year']);
    }

    /**
     * A single name or a list of them; anything else is a mistake worth saying
     * out loud rather than silently dropping.
     *
     * @return array<int, string>
     */
    private static function names(mixed $value, int $line): array
    {
        if ($value === null) {
            return [];
        }

        $value = is_string($value) ? [$value] : $value;

        if (! is_array($value) || ! array_is_list($value)) {
            throw new RuntimeException("Entrée {$line} : « chapters » et « pages » attendent une liste de noms.");
        }

        $names = [];

        foreach ($value as $name) {
            if (! is_string($name)) {
                throw new RuntimeException("Entrée {$line} : « chapters » et « pages » attendent une liste de noms.");
            }

            $name = trim($name);

            if ($name !== '' && ! in_array($name, $names, true)) {
                $names[] = mb_substr($name, 0, 120);
            }
        }

        if (count($names) > self::MAX_UNITS) {
            throw new RuntimeException("Entrée {$line} : ".self::MAX_UNITS.' éléments au maximum.');
        }

        return $names;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 200);
    }
}
