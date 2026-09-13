<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * One dated line of a language's programme: a test on a date, and the units it
 * covers. Shared by everyone studying that language.
 */
#[Fillable(['language_id', 'date', 'title', 'note'])]
class ProgramEntry extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    /**
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * @return BelongsToMany<Unit, $this>
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->orderBy('kind')->orderBy('position');
    }

    public function isPast(): bool
    {
        return $this->date->lt(Carbon::today());
    }

    public function isToday(): bool
    {
        return $this->date->isSameDay(Carbon::today());
    }

    /**
     * Days from today, negative once the date is behind us.
     */
    public function daysAway(): int
    {
        return (int) Carbon::today()->diffInDays($this->date, false);
    }
}
