<?php

namespace App\Models;

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
     * No password is stored: this app uses username-only sign-in.
     */
    public function getAuthPassword(): string
    {
        return '';
    }
}
