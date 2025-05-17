<?php

declare(strict_types=1);

namespace Akira\Followable\Concerns;

use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;
use Akira\Followable\Followable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\LazyCollection;

use function class_uses;

trait Follower
{
    /**
     * Follow a followable model.
     *
     * @throws CannotFollowYourSelfException|FollowableTraitNotFoundException
     */
    public function follow(Model $followable): array
    {

        if ($followable->is($this)) {
            throw new CannotFollowYourSelfException();
        }

        $this->ensureFollowableTraitExists($followable);

        $isPending = $this->followIsPending($followable);

        $this->processFollow($followable, $isPending);

        return ['pending' => $isPending];
    }

    /**
     * Unfollow a followable model.
     *
     * @throws FollowableTraitNotFoundException
     */
    public function unfollow(Model $followable): void
    {

        $this->ensureFollowableTraitExists($followable);

        $this->followings()->of($followable)->get()->each->delete();
    }

    /**
     * Toggle follow a followable model.
     *
     * @throws FollowableTraitNotFoundException|CannotFollowYourSelfException
     */
    public function toggleFollow(Model $followable): void
    {

        $this->isFollowing($followable)
            ? $this->unfollow($followable)
            : $this->follow($followable);
    }

    /**
     * Check if the model is following a followable model.
     *
     * @throws FollowableTraitNotFoundException
     */
    public function isFollowing(Model $followable): bool
    {

        $this->ensureFollowableTraitExists($followable);

        if ($this->relationLoaded('followings')) {
            return $this->getFollowings($followable);
        }

        return $this->followings()->of($followable)->accepted()->exists();
    }

    /**
     * Check if the model has requested to follow a followable model.
     *
     * @throws FollowableTraitNotFoundException
     */
    public function hasRequestedToFollow(Model $followable): bool
    {

        $this->ensureFollowableTraitExists($followable);

        if ($this->relationLoaded('followings')) {
            return $this->followings->whereNull('accepted_at')
                ->where('followable_id', $followable->getKey())
                ->where('followable_type', $followable->getMorphClass())
                ->isNotEmpty();
        }

        return $this->followings()->of($followable)->notAccepted()->exists();
    }

    /**
     * Get the followings has many relationship.
     *
     * @throws FollowableTraitNotFoundException
     */
    public function followings(): HasMany
    {

        return $this->hasMany(
            config('followable.followables_model', Followable::class),
            config('follow.user_foreign_key', 'user_id'),
            $this->getKeyName(),
        );
    }

    /**
     * Get approved followings.
     */
    public function approvedFollowings(): HasMany
    {

        return $this->followings()->accepted();
    }

    /**
     * Get not approved followings.
     */
    public function notApprovedFollowings(): HasMany
    {

        return $this->followings()->notAccepted();
    }

    /**
     * Attach follow status to followables.
     */
    public function attachFollowStatus(
        Model|SupportCollection|LengthAwarePaginator|Paginator|LazyCollection|array $followables,
        bool $returnFirst = false,
        ?callable $resolver = null,
    ): mixed {

        $followables = $this->handleFollowables($followables);

        $followed = $this->followings;

        $this->mapFollowables($followables, $followed, $resolver);

        return $returnFirst ? $followables->first() : $followables;
    }

    /**
     * Ensure the followable trait exists.
     */
    private function ensureFollowableTraitExists(Model $followable): void
    {

        if (! in_array(\Akira\Followable\Concerns\Followable::class, class_uses($followable))) {
            throw new FollowableTraitNotFoundException();
        }
    }

    /**
     * Get the followings of a followable model.
     */
    private function getFollowings(Model $followable)
    {

        return $this->followings
            ->whereNotNull('accepted_at')
            ->where('followable_id', $followable->getKey())
            ->where('followable_type', $followable->getMorphClass())
            ->isNotEmpty();
    }

    /**
     * Map followables.
     */
    private function mapFollowables(
        SupportCollection $followables,
        Collection $followed,
        ?callable $resolver,
    ): void {

        $followedMap = $followed->keyBy(fn ($item): string => "{$item->followable_type}:{$item->followable_id}");

        $resolver ??= fn ($m) => $m;

        $followables->each(function ($followable) use ($followedMap, $resolver): void {

            $followable = $resolver($followable);

            if (is_object($followable)
                && in_array(Follower::class, class_uses_recursive($followable))
            ) {
                $key = "{$followable->getMorphClass()}:{$followable->getKey()}";
                $item = $followedMap->get($key);

                $followable->has_followed = (bool) $item;
                $followable->followed_at = $item?->created_at;
                $followable->follow_accepted_at = $item?->accepted_at;
            }
        });
    }

    /**
     * Process follow.
     */
    private function processFollow(Model|Followable $followable, bool $isPending): void
    {

        $this->followings()->updateOrCreate([
            'followable_id' => $followable->getKey(),
            'followable_type' => $followable->getMorphClass(),
        ], [
            'accepted_at' => $isPending ? null : now(),
        ]);
    }

    /**
     * Check if the follow is pending.
     */
    private function followIsPending(Model $followable): bool
    {

        return $followable->needsToApproveFollowRequests() ?: false;
    }

    /**
     * Handle followables.
     */
    private function handleFollowables(
        Model|SupportCollection|LengthAwarePaginator|Paginator|LazyCollection|array $followables,
    ): SupportCollection {

        return match (true) {
            $followables instanceof Model => collect([$followables]),
            $followables instanceof LengthAwarePaginator => $followables = $followables->getCollection(),
            $followables instanceof Paginator || $followables instanceof CursorPaginator => $followables
                = collect($followables->items()),
            $followables instanceof LazyCollection => $followables
                = collect(iterator_to_array($followables->getIterator())),
            default => collect($followables)
        };
    }
}
