# API Reference

Complete reference of all public methods, parameters, and return types.

## Follower Trait Methods

### `follow(Model $followable): array`

Follow another model.

- **Parameters:** `Model $followable` - Model to follow
- **Returns:** `['pending' => bool]`
- **Throws:** `CannotFollowYourSelfException`, `FollowableTraitNotFoundException`

### `unfollow(Model $followable): void`

Unfollow a model.

- **Parameters:** `Model $followable` - Model to unfollow
- **Returns:** `void`
- **Throws:** `FollowableTraitNotFoundException`

### `toggleFollow(Model $followable): void`

Toggle follow state.

- **Parameters:** `Model $followable` - Model to toggle
- **Returns:** `void`
- **Throws:** `FollowableTraitNotFoundException`, `CannotFollowYourSelfException`

### `isFollowing(Model $followable): bool`

Check if following a model.

- **Parameters:** `Model $followable` - Model to check
- **Returns:** `bool`
- **Throws:** `FollowableTraitNotFoundException`

### `hasRequestedToFollow(Model $followable): bool`

Check if has pending follow request.

- **Parameters:** `Model $followable` - Model to check
- **Returns:** `bool`
- **Throws:** `FollowableTraitNotFoundException`

### `followings(): HasMany`

Get all follow records.

- **Returns:** `HasMany` relationship

### `approvedFollowings(): HasMany`

Get accepted follow records.

- **Returns:** `HasMany` relationship

### `notApprovedFollowings(): HasMany`

Get pending follow records.

- **Returns:** `HasMany` relationship

### `attachFollowStatus($followables, bool $returnFirst = false, ?callable $resolver = null): mixed`

Attach follow status to models.

- **Parameters:**
  - `$followables` - Models to attach status to
  - `bool $returnFirst` - Return first item only
  - `?callable $resolver` - Optional resolver function
- **Returns:** Collection or single model

## Followable Trait Methods

### `needsToApproveFollowRequests(): bool`

Determine if approval is required.

- **Returns:** `bool`

### `acceptFollowRequestFrom(Model $follower): void`

Accept a follow request.

- **Parameters:** `Model $follower` - Follower to accept
- **Returns:** `void`
- **Throws:** `FollowerTraitNotFoundException`

### `rejectFollowRequestFrom(Model $follower): void`

Reject a follow request.

- **Parameters:** `Model $follower` - Follower to reject
- **Returns:** `void`
- **Throws:** `FollowerTraitNotFoundException`

### `isFollowedBy(Model $follower): bool`

Check if followed by a user.

- **Parameters:** `Model $follower` - Potential follower
- **Returns:** `bool`
- **Throws:** `FollowerTraitNotFoundException`

### `followables(): HasMany`

Get all follow records.

- **Returns:** `HasMany` relationship

### `followers(): BelongsToMany`

Get all followers.

- **Returns:** `BelongsToMany` relationship

### `approvedFollowers(): BelongsToMany`

Get accepted followers.

- **Returns:** `BelongsToMany` relationship

### `notApprovedFollowers(): BelongsToMany`

Get pending followers.

- **Returns:** `BelongsToMany` relationship

### `scopeOrderByFollowersCount($query, string $direction = 'desc')`

Order by follower count.

- **Parameters:**
  - `$query` - Query builder
  - `string $direction` - Sort direction

### `scopeOrderByFollowersCountDesc($query)`

Order by follower count descending.

- **Parameters:** `$query` - Query builder

### `scopeOrderByFollowersCountAsc($query)`

Order by follower count ascending.

- **Parameters:** `$query` - Query builder

## Followable Model Scopes

### `scopeWithType(Builder $query, string $type): Builder`

Filter by followable type.

- **Parameters:**
  - `Builder $query`
  - `string $type` - Class name

### `scopeOf(Builder $query, Model $model): Builder`

Filter by specific model.

- **Parameters:**
  - `Builder $query`
  - `Model $model`

### `scopeFollowedBy(Builder $query, Model $follower): Builder`

Filter by follower.

- **Parameters:**
  - `Builder $query`
  - `Model $follower`

### `scopeAccepted(Builder $query): Builder`

Filter to accepted follows.

- **Parameters:** `Builder $query`

### `scopeNotAccepted(Builder $query): Builder`

Filter to pending follows.

- **Parameters:** `Builder $query`

---

**Previous:** [Extending](16-extending.md)
