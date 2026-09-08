<?php

namespace App\Support;

use JsonException;
use RuntimeException;

/**
 * Turns a pasted JSON vocabulary list into card rows.
 *
 * Two shapes are accepted. The plain one is a français → allemand map:
 *
 *     {"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}
 *
 * The longer one is a list, for when an entry needs an example or a heading:
 *
 *     [{"fr": "la maison", "de": "das Haus, ¨er", "ex": "zu Hause", "sec": "Die Wohnung"}]
 */
class WordList
{
    public const MAX_ENTRIES = 1000;

    private const MAX_LENGTH = 500;

    /**
     * @return array<int, array{section: string|null, de: string, fr: string, example: string|null}>
     *
     * @throws RuntimeException with a message meant for the user
     */
    public static function parse(string $json): array
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
            throw new RuntimeException('Le JSON doit être un objet « français → allemand » ou une liste d\'entrées.');
        }

        $rows = array_is_list($decoded)
            ? self::fromList($decoded)
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

        foreach ($decoded as $fr => $de) {
            if (! is_string($de)) {
                throw new RuntimeException("La valeur de « {$fr} » doit être une chaîne (l'allemand).");
            }

            $row = self::row((string) $fr, $de, null, null);

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
    private static function fromList(array $decoded): array
    {
        $rows = [];

        foreach ($decoded as $i => $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException('Entrée '.($i + 1).' : un objet {"fr": …, "de": …} est attendu.');
            }

            $fr = $entry['fr'] ?? $entry['french'] ?? $entry['français'] ?? null;
            $de = $entry['de'] ?? $entry['german'] ?? $entry['allemand'] ?? null;

            if (! is_string($fr) || ! is_string($de)) {
                throw new RuntimeException('Entrée '.($i + 1).' : « fr » et « de » sont obligatoires.');
            }

            $row = self::row($fr, $de, $entry['ex'] ?? $entry['example'] ?? null, $entry['sec'] ?? $entry['section'] ?? null);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string|null>|null
     */
    private static function row(string $fr, string $de, mixed $example, mixed $section): ?array
    {
        $fr = trim($fr);
        $de = trim($de);

        if ($fr === '' || $de === '') {
            return null;
        }

        foreach ([$fr, $de] as $side) {
            if (mb_strlen($side) > self::MAX_LENGTH) {
                throw new RuntimeException('Une entrée dépasse '.self::MAX_LENGTH.' caractères.');
            }
        }

        return [
            'fr' => $fr,
            'de' => $de,
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
