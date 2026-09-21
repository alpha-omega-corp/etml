<?php

namespace App\Models;

use App\Support\UnitKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['username'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /**
     * @return HasMany<CardState, $this>
     */
    public function cardStates(): HasMany
    {
        return $this->hasMany(CardState::class);
    }

    /**
     * The decks this user cut for themselves.
     *
     * @return HasMany<Unit, $this>
     */
    public function selections(): HasMany
    {
        return $this->hasMany(Unit::class)->where('kind', UnitKind::Selection)->orderBy('position');
    }

    /**
     * No password is stored: this app uses username-only sign-in.
     */
    public function getAuthPassword(): string
    {
        return '';
    }
}
