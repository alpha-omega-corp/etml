<?php

namespace App\Support;

use JsonException;
use RuntimeException;

/**
 * Turns a pasted JSON word list into card rows, for any language.
 *
 * Two shapes are accepted. The plain one is a français → langue map:
 *
 *     {"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}
 *
 * The longer one is a list, for when an entry needs an example or a heading.
 * The foreign side is keyed by the language's own code, so the same list reads
 * the same way in German and in English:
 *
 *     [{"fr": "la maison", "de": "das Haus, ¨er", "ex": "zu Hause", "sec": "Die Wohnung"}]
 *     [{"fr": "la maison", "en": "the house", "ex": "at home", "sec": "The flat"}]
 */
class WordList
{
    public const MAX_ENTRIES = 1000;

    private const MAX_LENGTH = 500;

    /**
     * @param  string  $code  the language's code, e.g. `de` — the key the foreign side may use
     * @return array<int, array{section: string|null, term: string, translation: string, example: string|null}>
     *
     * @throws RuntimeException with a message meant for the user
     */
    public static function parse(string $json, string $code = 'de'): array
    {
        $json = trim($json);

        if ($json === '') {
            throw new RuntimeException('Collez la liste de mots au format JSON.');
        }

        try {
            $decoded = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('JSON invalide : '.$e->getMessage());
        }

        if (! is_array($decoded) || $decoded === []) {
            throw new RuntimeException('Le JSON doit être un objet « français → langue » ou une liste d\'entrées.');
        }

        $rows = array_is_list($decoded)
            ? self::fromList($decoded, $code)
            : self::fromMap($decoded);

        if ($rows === []) {
            throw new RuntimeException('Aucun mot lisible dans ce JSON.');
        }

        if (count($rows) > self::MAX_ENTRIES) {
            throw new RuntimeException(self::MAX_ENTRIES.' mots au maximum (reçu '.count($rows).').');
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<int, array<string, string|null>>
     */
    private static function fromMap(array $decoded): array
    {
        $rows = [];

        foreach ($decoded as $translation => $term) {
            if (! is_string($term)) {
                throw new RuntimeException("La valeur de « {$translation} » doit être une chaîne (le mot étranger).");
            }

            $row = self::row((string) $translation, $term, null, null);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $decoded
     * @return array<int, array<string, string|null>>
     */
    private static function fromList(array $decoded, string $code): array
    {
        $rows = [];

        foreach ($decoded as $i => $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException('Entrée '.($i + 1).' : un objet {"fr": …, "'.$code.'": …} est attendu.');
            }

            $translation = $entry['fr'] ?? $entry['french'] ?? $entry['français'] ?? $entry['translation'] ?? null;
            $term = $entry[$code] ?? $entry['term'] ?? $entry['mot'] ?? null;

            if (! is_string($translation) || ! is_string($term)) {
                throw new RuntimeException('Entrée '.($i + 1).' : « fr » et « '.$code.' » sont obligatoires.');
            }

            $row = self::row($translation, $term, $entry['ex'] ?? $entry['example'] ?? null, $entry['sec'] ?? $entry['section'] ?? null);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string|null>|null
     */
    private static function row(string $translation, string $term, mixed $example, mixed $section): ?array
    {
        $translation = trim($translation);
        $term = trim($term);

        if ($translation === '' || $term === '') {
            return null;
        }

        foreach ([$translation, $term] as $side) {
            if (mb_strlen($side) > self::MAX_LENGTH) {
                throw new RuntimeException('Une entrée dépasse '.self::MAX_LENGTH.' caractères.');
            }
        }

        return [
            'translation' => $translation,
            'term' => $term,
            'example' => self::clean($example),
            'section' => self::clean($section),
        ];
    }

    private static function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, self::MAX_LENGTH);
    }
}
