<?php

namespace App\Support;

use LogicException;

/**
 * The kinds of deck a language carries. They all play the exact same card
 * game; only what they are called changes — vocabulary is cut into chapters,
 * verbs into pages, and a selection is the deck a user cuts for themselves out
 * of a list they were given.
 */
enum UnitKind: string
{
    case Vocabulary = 'vocabulary';

    case Verbs = 'verbs';

    case Selection = 'selection';

    /**
     * The URL segment, so a deck reads `/allemand/vocabulaire/3`.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Vocabulary => 'vocabulaire',
            self::Verbs => 'verbes',
            self::Selection => 'selections',
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
            self::Selection => 'Mes sélections',
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
            self::Selection => 'sélection',
        };
    }

    public function plural(): string
    {
        return match ($this) {
            self::Vocabulary => 'chapitres',
            self::Verbs => 'pages',
            self::Selection => 'sélections',
        };
    }

    /**
     * A selection belongs to the one user who cut it; a chapter and a verb
     * page belong to everybody.
     */
    public function isPersonal(): bool
    {
        return $this === self::Selection;
    }

    /**
     * The kinds a language ships — the ones a programme can name and an
     * administrator fills from a word list.
     *
     * @return array<int, self>
     */
    public static function shared(): array
    {
        return array_values(array_filter(self::cases(), fn (self $kind) => ! $kind->isPersonal()));
    }

    /**
     * French has genders and the interface has to agree with them: a chapter
     * is masculine, a page and a selection feminine. These five say the same
     * phrase correctly for all three, so no view has to.
     */
    public function newLabel(): string
    {
        return match ($this) {
            self::Vocabulary => 'Nouveau chapitre',
            self::Verbs => 'Nouvelle page',
            self::Selection => 'Nouvelle sélection',
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
            self::Selection => 'cette sélection',
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
            self::Selection => 'une sélection',
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
            self::Selection => 'de la sélection',
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
            self::Selection => 'Aucune sélection',
        };
    }

    /**
     * The key a pasted program uses to list units of this kind. Only the
     * shared kinds have one: a programme never names someone's selection.
     */
    public function programKey(): string
    {
        return match ($this) {
            self::Vocabulary => 'chapters',
            self::Verbs => 'pages',
            self::Selection => throw new LogicException('Une sélection ne figure jamais dans un programme.'),
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
