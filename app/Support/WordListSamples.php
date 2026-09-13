<?php

namespace App\Support;

use App\Models\Language;

/**
 * The example payloads shown on the creation page and on an empty unit. They
 * are written in whichever language the deck belongs to, so the sample a user
 * copies is always one they could actually paste.
 */
class WordListSamples
{
    /**
     * A pair of words per known language: what « la maison » and « la cuisine »
     * look like there. Any language without an entry falls back to the first.
     *
     * @var array<string, array{house: string, kitchen: string, example: string, section: string}>
     */
    private const WORDS = [
        'de' => ['house' => 'das Haus, ¨er', 'kitchen' => 'die Küche, n', 'example' => 'zu Hause → à la maison', 'section' => 'Die Wohnung'],
        'en' => ['house' => 'the house', 'kitchen' => 'the kitchen', 'example' => 'at home → à la maison', 'section' => 'The flat'],
        'es' => ['house' => 'la casa', 'kitchen' => 'la cocina', 'example' => 'en casa → à la maison', 'section' => 'La vivienda'],
        'it' => ['house' => 'la casa', 'kitchen' => 'la cucina', 'example' => 'a casa → à la maison', 'section' => 'La casa'],
    ];

    /**
     * @return array{wordsPlaceholder: string, sampleMap: string, sampleList: string, sampleCode: string}
     */
    public static function for(Language $language): array
    {
        $code = $language->code;
        $words = self::WORDS[$code] ?? reset(self::WORDS);

        $map = <<<JSON
            {
              "la maison": "{$words['house']}",
              "la cuisine": "{$words['kitchen']}"
            }
            JSON;

        $list = <<<JSON
            [
              {"fr": "la maison", "{$code}": "{$words['house']}",
               "ex": "{$words['example']}", "sec": "{$words['section']}"}
            ]
            JSON;

        return [
            'wordsPlaceholder' => '{"la maison": "'.$words['house'].'", "la cuisine": "'.$words['kitchen'].'"}',
            'sampleMap' => $map,
            'sampleList' => $list,
            'sampleCode' => $code,
        ];
    }
}
