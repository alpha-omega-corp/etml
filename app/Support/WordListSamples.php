<?php

namespace App\Support;

/**
 * The example payloads shown on the creation page and on an empty chapter.
 */
class WordListSamples
{
    public const PLACEHOLDER = '{"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}';

    public const MAP = <<<'JSON'
        {
          "la maison": "das Haus, ¨er",
          "la cuisine": "die Küche, n"
        }
        JSON;

    public const LIST = <<<'JSON'
        [
          {"fr": "la maison", "de": "das Haus, ¨er",
           "ex": "zu Hause → à la maison", "sec": "Die Wohnung"}
        ]
        JSON;

    /**
     * @return array{wordsPlaceholder: string, sampleMap: string, sampleList: string}
     */
    public static function all(): array
    {
        return [
            'wordsPlaceholder' => self::PLACEHOLDER,
            'sampleMap' => self::MAP,
            'sampleList' => self::LIST,
        ];
    }
}
