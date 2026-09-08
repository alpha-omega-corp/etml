<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['section', 'de', 'fr', 'example', 'position'])]
class Card extends Model
{
    /**
     * @return HasMany<CardState, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(CardState::class);
    }
}
