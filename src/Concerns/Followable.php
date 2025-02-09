<?php

declare(strict_types=1);

namespace Akira\Followable\Concerns;

use Akira\Followable\Exceptions\FollowerTraitNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait Followable
{
    /**
     *  Needs to approve follow requests.
     */
    public function needsToApproveFollowRequests(): bool
    {
        return false;
    }

    /**
     *  Reject follow request from a follower.
     *
     * @throws FollowerTraitNotFoundException
     */
    public function rejectFollowRequestFrom(Model $follower): void
    {

        $this->ensureFollowerTraitExists($follower);

        $this->followables()->followedBy($follower)->get()->each->delete();
    }

    /**
     * Accept follow request from a follower.
     *
     * @throws FollowerTraitNotFoundException
     */
    public function acceptFollowRequestFrom(Model $follower): void
    {

        $this->ensureFollowerTraitExists($follower);

        $this->followables()->followedBy($follower)->get()->each->update(['accepted_at' => now()]);
    }

    /**
     * Check if the model is followed by a follower.
     *
     * @throws FollowerTraitNotFoundException
     */
    public function isFollowedBy(Model $follower): bool
    {

        $this->ensureFollowerTraitExists($follower);

        if ($this->relationLoaded('followables')) {
            return $this->followables->whereNotNull('accepted_at')->contains($follower);
        }

        return $this->followables()->accepted()->followedBy($follower)->exists();
    }

    /**
     * Order by followers count.
     */
    public function scopeOrderByFollowersCount($query, string $direction = 'desc')
    {
        return $query->withCount('followers')->orderBy('followers_count', $direction);
    }

    /**
     * Order by followers count descending.
     */
    public function scopeOrderByFollowersCountDesc($query)
    {
        return $this->scopeOrderByFollowersCount($query, 'desc');
    }

    /**
     * Order by followers count ascending.
     */
    public function scopeOrderByFollowersCountAsc($query)
    {
        return $this->scopeOrderByFollowersCount($query, 'asc');
    }

    /**
     * Get the followers has many relationship.
     */
    public function followables(): HasMany
    {
        return $this->hasMany(
            config('followable.followables_model', \Akira\Followable\Followable::class),
            'followable_id',
        )->where('followable_type', $this->getMorphClass());
    }

    /**
     * Get the followers belongs to many relationship.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('followable.followables_table', 'followables'),
            'followable_id',
            config('follow.user_foreign_key', 'user_id')
        )
            ->where('followable_type', $this->getMorphClass())
            ->withPivot(['accepted_at']);
    }

    /**
     * Get the approved followers belongs to many relationship.
     */
    public function approvedFollowers(): BelongsToMany
    {
        return $this->followers()->whereNotNull('accepted_at');
    }

    /**
     * Get the not approved followers belongs to many relationship.
     */
    public function notApprovedFollowers(): BelongsToMany
    {
        return $this->followers()->whereNull('accepted_at');
    }

    /**
     * Ensure the follower trait exists.
     *
     * @throws FollowerTraitNotFoundException
     */
    private function ensureFollowerTraitExists(Model $follower): void
    {

        if (! in_array(Follower::class, class_uses($follower))) {
            throw new FollowerTraitNotFoundException();
        }
    }
}
