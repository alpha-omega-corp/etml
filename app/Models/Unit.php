<?php

namespace App\Models;

use App\Support\UnitKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One deck of cards inside a language: a vocabulary chapter or a verb page.
 * The kind only decides what it is called and where it is listed — the card
 * game itself is the same for both.
 */
#[Fillable(['language_id', 'kind', 'name', 'position'])]
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
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
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
     * An empty unit is the only one that accepts an import, so a deck can
     * never be silently doubled.
     */
    public function isEmpty(): bool
    {
        return $this->cards()->count() === 0;
    }

    /**
     * The next free position in its own language and kind.
     */
    public static function nextPosition(Language $language, UnitKind $kind): int
    {
        return (int) self::where('language_id', $language->id)
            ->where('kind', $kind)
            ->max('position') + 1;
    }
}
