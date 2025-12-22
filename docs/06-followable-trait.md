# Followable Trait

The `Followable` trait provides methods for models that can be followed by other entities. This guide covers all available methods, relationships, and query scopes.

## Overview

Add the `Followable` trait to any model that needs to be followable:

```php
namespace App\Models;

use Akira\Followable\Concerns\Followable;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Followable;
}
```

## Public Methods

### `needsToApproveFollowRequests(): bool`

Determine whether follow requests require approval. Override this method to implement custom approval logic.

**Returns:**
- `bool` - `true` if approval is required, `false` otherwise

**Default:**
- Returns `false` (auto-approve all follows)

**Example:**

```php
class User extends Model
{
    use Followable;
    
    /**
     * Private accounts require approval
     */
    public function needsToApproveFollowRequests(): bool
    {
        return $this->is_private;
    }
}
```

**Advanced Examples:**

```php
// Require approval based on follower reputation
public function needsToApproveFollowRequests(): bool
{
    $follower = auth()->user();
    
    if (!$follower) {
        return true;
    }
    
    // Auto-approve for verified users
    if ($follower->is_verified) {
        return false;
    }
    
    // Require approval for new accounts
    if ($follower->created_at->gt(now()->subDays(7))) {
        return true;
    }
    
    return $this->is_private;
}

// Time-based approval
public function needsToApproveFollowRequests(): bool
{
    // Don't require approval during business hours
    $hour = now()->hour;
    if ($hour >= 9 && $hour <= 17) {
        return false;
    }
    
    return $this->is_private;
}
```

### `acceptFollowRequestFrom(Model $follower): void`

Accept a pending follow request from a follower.

**Parameters:**
- `$follower` - The user requesting to follow (must use the `Follower` trait)

**Throws:**
- `FollowerTraitNotFoundException` - If follower model doesn't use `Follower` trait

**Example:**

```php
$user = User::find(1);
$follower = User::find(2);

// Accept the follow request
$user->acceptFollowRequestFrom($follower);

// Verify it was accepted
if ($user->isFollowedBy($follower)) {
    echo "Follow request accepted";
}
```

**Bulk Accept:**

```php
// Accept all pending requests
$pendingFollowers = $user->notApprovedFollowers;

foreach ($pendingFollowers as $follower) {
    $user->acceptFollowRequestFrom($follower);
}
```

### `rejectFollowRequestFrom(Model $follower): void`

Reject and delete a pending follow request from a follower.

**Parameters:**
- `$follower` - The user requesting to follow (must use the `Follower` trait)

**Throws:**
- `FollowerTraitNotFoundException` - If follower model doesn't use `Follower` trait

**Example:**

```php
$user = User::find(1);
$follower = User::find(2);

// Reject the follow request
$user->rejectFollowRequestFrom($follower);

// Verify it was rejected
if (!$follower->hasRequestedToFollow($user)) {
    echo "Follow request rejected";
}
```

**Automated Rejection:**

```php
// Reject requests from blocked users
public function rejectBlockedFollowers()
{
    $blockedUserIds = $this->blockedUsers()->pluck('id');
    
    $pendingFromBlocked = $this->notApprovedFollowers()
        ->whereIn('id', $blockedUserIds)
        ->get();
    
    foreach ($pendingFromBlocked as $follower) {
        $this->rejectFollowRequestFrom($follower);
    }
}
```

### `isFollowedBy(Model $follower): bool`

Check if this model is being followed by a specific follower.

**Parameters:**
- `$follower` - The potential follower (must use the `Follower` trait)

**Returns:**
- `bool` - `true` if followed (and accepted), `false` otherwise

**Throws:**
- `FollowerTraitNotFoundException` - If follower model doesn't use `Follower` trait

**Example:**

```php
if ($user->isFollowedBy($otherUser)) {
    echo "This user follows you";
}

// Check multiple followers
$followers = User::whereIn('id', $followerIds)->get();
foreach ($followers as $follower) {
    if ($user->isFollowedBy($follower)) {
        echo "{$follower->name} follows this user";
    }
}
```

**Performance Note:**

If the `followables` relationship is loaded, no additional query is performed:

```php
// Efficient: Single query
$user->load('followables');
foreach ($potentialFollowers as $follower) {
    $isFollowed = $user->isFollowedBy($follower); // Uses loaded data
}
```

## Relationships

### `followables(): HasMany`

Get all follow records for this model (both pending and accepted).

**Returns:**
- `HasMany` - Relationship to `Followable` model

**Example:**

```php
// Get all follow records
$followRecords = $user->followables;

// Filter by date
$recentFollows = $user->followables()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// Count total follow attempts
$totalFollows = $user->followables()->count();
```

### `followers(): BelongsToMany`

Get all users following this model (both pending and accepted).

**Returns:**
- `BelongsToMany` - Many-to-many relationship to user models

**Pivot Data:**
- `accepted_at` - Timestamp when follow was accepted

**Example:**

```php
// Get all followers
$followers = $user->followers;

// With pagination
$followers = $user->followers()->paginate(20);

// Access pivot data
foreach ($user->followers as $follower) {
    echo "Followed at: {$follower->pivot->accepted_at}";
}

// Order by follow date
$followers = $user->followers()
    ->orderBy('followables.created_at', 'desc')
    ->get();
```

### `approvedFollowers(): BelongsToMany`

Get only accepted followers.

**Returns:**
- `BelongsToMany` - Relationship filtered to accepted follows

**Example:**

```php
$approvedFollowers = $user->approvedFollowers;

// Count approved followers
$approvedCount = $user->approvedFollowers()->count();

// Get with user details
$followers = $user->approvedFollowers()
    ->with('profile')
    ->get();
```

### `notApprovedFollowers(): BelongsToMany`

Get only pending followers (awaiting approval).

**Returns:**
- `BelongsToMany` - Relationship filtered to pending follows

**Example:**

```php
$pendingFollowers = $user->notApprovedFollowers;

// Show pending requests in UI
foreach ($user->notApprovedFollowers as $follower) {
    echo "{$follower->name} wants to follow you";
}

// Count pending requests
$pendingCount = $user->notApprovedFollowers()->count();
```

## Query Scopes

### `scopeOrderByFollowersCount($query, string $direction = 'desc')`

Order models by their follower count.

**Parameters:**
- `$query` - Query builder instance
- `$direction` - Sort direction ('desc' or 'asc')

**Example:**

```php
// Most followed users
$popularUsers = User::orderByFollowersCount('desc')->take(10)->get();

// Least followed users
$unpopularUsers = User::orderByFollowersCount('asc')->take(10)->get();

// With additional filters
$popularActiveUsers = User::where('is_active', true)
    ->orderByFollowersCount()
    ->paginate(20);
```

### `scopeOrderByFollowersCountDesc($query)`

Order models by follower count in descending order (most followed first).

**Parameters:**
- `$query` - Query builder instance

**Example:**

```php
// Top 10 most followed users
$topUsers = User::orderByFollowersCountDesc()->take(10)->get();

// Most followed posts
$popularPosts = Post::orderByFollowersCountDesc()
    ->where('published', true)
    ->take(5)
    ->get();
```

### `scopeOrderByFollowersCountAsc($query)`

Order models by follower count in ascending order (least followed first).

**Parameters:**
- `$query` - Query builder instance

**Example:**

```php
// Users with fewest followers
$newUsers = User::orderByFollowersCountAsc()
    ->where('created_at', '>=', now()->subDays(30))
    ->get();
```

## Practical Examples

### Approval Dashboard

```php
public function followRequests()
{
    $user = auth()->user();
    
    $pendingRequests = $user->notApprovedFollowers()
        ->with('profile')
        ->latest('followables.created_at')
        ->paginate(20);
    
    return view('follow-requests', compact('pendingRequests'));
}

// In Blade
@foreach($pendingRequests as $follower)
    <div class="request">
        <img src="{{ $follower->avatar }}" alt="{{ $follower->name }}">
        <h3>{{ $follower->name }}</h3>
        
        <form action="{{ route('follow-requests.accept', $follower) }}" method="POST">
            @csrf
            <button type="submit">Accept</button>
        </form>
        
        <form action="{{ route('follow-requests.reject', $follower) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit">Reject</button>
        </form>
    </div>
@endforeach
```

### Popular Users Widget

```php
public function popularUsers()
{
    return User::orderByFollowersCountDesc()
        ->take(5)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'followers_count' => $user->followers_count,
            ];
        });
}
```

### Auto-Moderation

```php
class User extends Model
{
    use Followable;
    
    /**
     * Override to implement auto-rejection logic
     */
    public function needsToApproveFollowRequests(): bool
    {
        $follower = auth()->user();
        
        if (!$follower) {
            return true;
        }
        
        // Auto-reject if follower is flagged
        if ($follower->is_flagged) {
            $this->rejectFollowRequestFrom($follower);
            return true;
        }
        
        // Auto-reject if too many pending requests
        if ($this->notApprovedFollowers()->count() > 100) {
            $this->rejectFollowRequestFrom($follower);
            return true;
        }
        
        return $this->is_private;
    }
}
```

### Follower Notifications

```php
use Akira\Followable\Events\Followed;
use Illuminate\Support\Facades\Event;

// In EventServiceProvider
Event::listen(Followed::class, function (Followed $event) {
    $follow = $event->follow;
    $follower = $follow->user;
    $followable = $follow->followable;
    
    if ($follow->accepted_at) {
        // Send notification for accepted follow
        $followable->notify(new NewFollowerNotification($follower));
    } else {
        // Send notification for pending request
        $followable->notify(new FollowRequestNotification($follower));
    }
});
```

### Follower Stats

```php
public function followerStats(User $user)
{
    return [
        'total_followers' => $user->followers()->count(),
        'approved_followers' => $user->approvedFollowers()->count(),
        'pending_requests' => $user->notApprovedFollowers()->count(),
        'followers_this_week' => $user->followables()
            ->where('created_at', '>=', now()->subWeek())
            ->whereNotNull('accepted_at')
            ->count(),
        'followers_this_month' => $user->followables()
            ->where('created_at', '>=', now()->subMonth())
            ->whereNotNull('accepted_at')
            ->count(),
    ];
}
```

---

**Previous:** [Follower Trait](05-follower-trait.md) | **Next:** [Approval Workflow](07-approval-workflow.md)
