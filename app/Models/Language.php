<?php

namespace App\Models;

use App\Support\UnitKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * A language the app teaches. Everything below it — the chapters, the verb
 * pages, the programme — hangs off this row, so adding a language is a row and
 * nothing else.
 */
#[Fillable(['name', 'slug', 'code', 'label', 'position'])]
class Language extends Model
{
    /** @use HasFactory<\Database\Factories\LanguageFactory> */
    use HasFactory;

    /**
     * @return HasMany<Unit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('position');
    }

    /**
     * Every card written under this language.
     *
     * @return HasManyThrough<Card, Unit, $this>
     */
    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, Unit::class);
    }

    /**
     * @return HasMany<ProgramEntry, $this>
     */
    public function programEntries(): HasMany
    {
        return $this->hasMany(ProgramEntry::class)->orderBy('date');
    }

    /**
     * @return Collection<int, self>
     */
    public static function ordered(): Collection
    {
        return self::orderBy('position')->get();
    }

    /**
     * The units of one kind, ordered, with their card count. A user only ever
     * sees what is shared plus what is theirs, so the reader is named.
     *
     * @return Collection<int, Unit>
     */
    public function unitsOfKind(UnitKind $kind, ?int $userId = null): Collection
    {
        return $this->units()
            ->where('kind', $kind)
            ->visibleTo($userId)
            ->withCount('cards')
            ->get();
    }

    /**
     * How the language names itself, which is what the back of a card is
     * titled: « Deutsch », « English ».
     */
    public function faceLabel(): string
    {
        return $this->label ?: $this->name;
    }

    /**
     * The short form used on the direction pill: FR → DE.
     */
    public function shortLabel(): string
    {
        return mb_strtoupper($this->code);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
