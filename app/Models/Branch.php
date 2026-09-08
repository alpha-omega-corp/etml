<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'position'])]
class Branch extends Model
{
    /**
     * @return HasMany<Chapter, $this>
     */
    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('position');
    }

    /**
     * Which subject a request is about: the one carried in the session, else the
     * first that actually has chapters, else simply the first. Kept here so the
     * page and the actions that post to it can never disagree.
     *
     * @param  Collection<int, self>  $branches  loaded with `withCount('chapters')`
     */
    public static function pick(Collection $branches, ?int $id): ?self
    {
        return $branches->firstWhere('id', $id)
            ?? $branches->first(fn (self $candidate) => $candidate->chapters_count > 0)
            ?? $branches->first();
    }

    /**
     * @return Collection<int, self>
     */
    public static function ordered(): Collection
    {
        return self::withCount('chapters')->orderBy('position')->get();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
