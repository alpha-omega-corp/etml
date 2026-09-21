<?php

namespace App\Models;

use App\Support\UnitKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * One deck of cards inside a language: a vocabulary chapter, a verb page, or
 * the selection a user cut for themselves. The kind only decides what it is
 * called, who sees it and where it is listed — the card game itself is the
 * same for all three.
 *
 * A unit is a LIST of cards (`card_unit`), not a bag of them: `cards.unit_id`
 * says which unit wrote a card and owns it, while the list says which units
 * play it. A chapter plays exactly the cards it wrote; a selection plays cards
 * a chapter wrote, which is what keeps « je sais » the same word everywhere.
 */
#[Fillable(['language_id', 'user_id', 'kind', 'name', 'position'])]
class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => UnitKind::class];
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * The one user a selection belongs to; null on everything shared.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The cards this unit plays, in the order it plays them.
     *
     * @return BelongsToMany<Card, $this>
     */
    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class)
            ->withPivot('position')
            ->orderBy('card_unit.position');
    }

    /**
     * The cards this unit wrote, whoever plays them. Deleting the unit deletes
     * these; a selection has none.
     *
     * @return HasMany<Card, $this>
     */
    public function ownCards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }

    /**
     * @return BelongsToMany<ProgramEntry, $this>
     */
    public function programEntries(): BelongsToMany
    {
        return $this->belongsToMany(ProgramEntry::class);
    }

    /**
     * The units one user may open: everything shared, plus their own.
     *
     * @param  Builder<Unit>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, ?int $userId): void
    {
        $query->where(fn (Builder $inner) => $inner
            ->whereNull('user_id')
            ->when($userId !== null, fn (Builder $mine) => $mine->orWhere('user_id', $userId)));
    }

    public function isVisibleTo(?int $userId): bool
    {
        return $this->user_id === null || ($userId !== null && $this->user_id === $userId);
    }

    public function isOwnedBy(?int $userId): bool
    {
        return $userId !== null && $this->user_id === $userId;
    }

    /**
     * An empty unit is the only one that accepts an import, so a deck can
     * never be silently doubled.
     */
    public function isEmpty(): bool
    {
        return $this->cards()->count() === 0;
    }

    /**
     * Put the unit's own cards on its list, in their printed order.
     *
     * Importing writes the cards in bulk — no model events — so the list is
     * built here afterwards, once. Running it again rebuilds the same list
     * rather than doubling it, which is what the seeder's re-runs need. Only
     * this unit's own list is touched: a selection that plays these cards is
     * left alone.
     */
    public function relistOwnCards(): void
    {
        DB::table('card_unit')->where('unit_id', $this->id)->delete();

        DB::table('card_unit')->insertUsing(
            ['card_id', 'unit_id', 'position'],
            DB::table('cards')->where('unit_id', $this->id)->select('id', 'unit_id', 'position'),
        );
    }

    /**
     * The next free position in its own language and kind — and, for a
     * selection, in its owner's own list.
     */
    public static function nextPosition(Language $language, UnitKind $kind, ?int $userId = null): int
    {
        return (int) self::where('language_id', $language->id)
            ->where('kind', $kind)
            ->where('user_id', $userId)
            ->max('position') + 1;
    }
}
