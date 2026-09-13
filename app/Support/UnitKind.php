<?php

namespace App\Support;

/**
 * The two kinds of deck a language carries. Both play the exact same card
 * game; only what they are called changes — vocabulary is cut into chapters,
 * verbs into pages.
 */
enum UnitKind: string
{
    case Vocabulary = 'vocabulary';

    case Verbs = 'verbs';

    /**
     * The URL segment, so a deck reads `/allemand/vocabulaire/3`.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Vocabulary => 'vocabulaire',
            self::Verbs => 'verbes',
        };
    }

    /**
     * What the section itself is called.
     */
    public function heading(): string
    {
        return match ($this) {
            self::Vocabulary => 'Vocabulaire',
            self::Verbs => 'Verbes',
        };
    }

    /**
     * What one unit of this kind is called, for buttons and messages.
     */
    public function singular(): string
    {
        return match ($this) {
            self::Vocabulary => 'chapitre',
            self::Verbs => 'page',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Vocabulary => 'chapitres',
            self::Verbs => 'pages',
        };
    }

    /**
     * French has genders and the interface has to agree with them: a chapter
     * is masculine, a page feminine. These four say the same phrase correctly
     * for both, so no view has to.
     */
    public function newLabel(): string
    {
        return match ($this) {
            self::Vocabulary => 'Nouveau chapitre',
            self::Verbs => 'Nouvelle page',
        };
    }

    /**
     * « ce chapitre » / « cette page ».
     */
    public function thisOne(): string
    {
        return match ($this) {
            self::Vocabulary => 'ce chapitre',
            self::Verbs => 'cette page',
        };
    }

    /**
     * « un chapitre » / « une page ».
     */
    public function aNew(): string
    {
        return match ($this) {
            self::Vocabulary => 'un chapitre',
            self::Verbs => 'une page',
        };
    }

    /**
     * « du chapitre » / « de la page ».
     */
    public function ofThe(): string
    {
        return match ($this) {
            self::Vocabulary => 'du chapitre',
            self::Verbs => 'de la page',
        };
    }

    /**
     * « Aucun chapitre » / « Aucune page ».
     */
    public function none(): string
    {
        return match ($this) {
            self::Vocabulary => 'Aucun chapitre',
            self::Verbs => 'Aucune page',
        };
    }

    /**
     * The key a pasted program uses to list units of this kind.
     */
    public function programKey(): string
    {
        return match ($this) {
            self::Vocabulary => 'chapters',
            self::Verbs => 'pages',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }
}
