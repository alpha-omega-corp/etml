<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'card_id', 'status'])]
class CardState extends Model
{
    public const STATUS_KNOWN = 'known';

    public const STATUS_REVIEW = 'review';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * How many cards the user has marked « je sais » on each of the given
     * units' lists — a selection counts the very same marks its source
     * chapter does. Feeds every progress figure on screen, so it lives here rather
     * than in the two controllers that need it.
     *
     * @param  array<int, int>  $unitIds
     * @return array<int, int> unit id => known cards
     */
    public static function knownPerUnit(?int $userId, array $unitIds): array
    {
        if ($userId === null || $unitIds === []) {
            return [];
        }

        return self::query()
            ->join('card_unit', 'card_unit.card_id', '=', 'card_states.card_id')
            ->where('card_states.user_id', $userId)
            ->where('card_states.status', self::STATUS_KNOWN)
            ->whereIn('card_unit.unit_id', $unitIds)
            ->groupBy('card_unit.unit_id')
            ->selectRaw('card_unit.unit_id as unit_id, count(*) as total')
            ->pluck('total', 'unit_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * The same figure rolled up per language, for the picker. It counts
     * cards, by the unit that wrote them, so a word in a selection is not
     * counted twice.
     *
     * @return array<int, int> language id => known cards
     */
    public static function knownPerLanguage(?int $userId): array
    {
        if ($userId === null) {
            return [];
        }

        return self::query()
            ->join('cards', 'cards.id', '=', 'card_states.card_id')
            ->join('units', 'units.id', '=', 'cards.unit_id')
            ->where('card_states.user_id', $userId)
            ->where('card_states.status', self::STATUS_KNOWN)
            ->groupBy('units.language_id')
            ->selectRaw('units.language_id as language_id, count(*) as total')
            ->pluck('total', 'language_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
