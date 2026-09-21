<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One two-sided card: `term` is the word in the language being learnt,
 * `translation` is the French side. Vocabulary and verbs share this shape.
 */
#[Fillable(['unit_id', 'section', 'term', 'translation', 'example', 'position'])]
class Card extends Model
{
    /** @use HasFactory<\Database\Factories\CardFactory> */
    use HasFactory;

    /**
     * The unit that wrote the card, and deletes it.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Every list the card is on: the unit that wrote it, plus the selections
     * users have put it in.
     *
     * @return BelongsToMany<Unit, $this>
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withPivot('position');
    }

    /**
     * @return HasMany<CardState, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(CardState::class);
    }
}
