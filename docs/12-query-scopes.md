# Query Scopes

Laravel Followable provides several query scopes to filter and order your data. This guide covers all available scopes on both the `Followable` model and models using the traits.

## Scopes on Followable Model

These scopes are available on the `Followable` model and can be used when querying follow relationships directly.

### `withType(string $type)`

Filter follows by the type of entity being followed.

**Parameters:**
- `$type` - Fully qualified class name

**Example:**

```php
use Akira\Followable\Followable;
use App\Models\User;
use App\Models\Post;

// Get all user follows
$userFollows = Followable::withType(User::class)->get();

// Get all post follows
$postFollows = Followable::withType(Post::class)->get();

// Count follows by type
$userFollowCount = Followable::withType(User::class)->count();

// With additional filters
$recentUserFollows = Followable::withType(User::class)
    ->where('created_at', '>=', now()->subDays(7))
    ->get();
```

### `of(Model $model)`

Filter follows of a specific model instance.

**Parameters:**
- `$model` - The specific model to filter by

**Example:**

```php
$user = User::find(1);

// Get all follows of this specific user
$follows = Followable::of($user)->get();

// Count followers
$followerCount = Followable::of($user)->accepted()->count();

// Get pending follows
$pendingFollows = Followable::of($user)->notAccepted()->get();
```

### `followedBy(Model $follower)`

Filter follows created by a specific user.

**Parameters:**
- `$follower` - The user who created the follows

**Example:**

```php
$user = User::find(1);

// Get all entities this user follows
$follows = Followable::followedBy($user)->get();

// Count what this user follows
$followingCount = Followable::followedBy($user)->accepted()->count();

// Get types of entities followed
$types = Followable::followedBy($user)
    ->select('followable_type')
    ->distinct()
    ->pluck('followable_type');
```

### `accepted()`

Filter to only accepted (approved) follows.

**Example:**

```php
// Get all accepted follows
$acceptedFollows = Followable::accepted()->get();

// Count accepted follows
$acceptedCount = Followable::accepted()->count();

// Recently accepted follows
$recentlyAccepted = Followable::accepted()
    ->where('accepted_at', '>=', now()->subDays(7))
    ->get();
```

### `notAccepted()`

Filter to only pending (not accepted) follows.

**Example:**

```php
// Get all pending follows
$pendingFollows = Followable::notAccepted()->get();

// Count pending follows
$pendingCount = Followable::notAccepted()->count();

// Oldest pending follows
$oldestPending = Followable::notAccepted()
    ->oldest('created_at')
    ->take(10)
    ->get();
```

## Scopes on Followable Trait

These scopes are available on models that use the `Followable` trait.

### `orderByFollowersCount(string $direction = 'desc')`

Order models by their follower count.

**Parameters:**
- `$direction` - Sort direction: `'desc'` or `'asc'` (default: `'desc'`)

**Example:**

```php
// Most followed users
$popularUsers = User::orderByFollowersCount('desc')
    ->take(10)
    ->get();

// Least followed users
$unpopularUsers = User::orderByFollowersCount('asc')
    ->take(10)
    ->get();

// With pagination
$users = User::orderByFollowersCount()
    ->paginate(20);

// With additional filters
$popularActiveUsers = User::where('is_active', true)
    ->where('created_at', '>=', now()->subYear())
    ->orderByFollowersCount('desc')
    ->get();
```

### `orderByFollowersCountDesc()`

Order models by follower count in descending order (most followed first). Shortcut for `orderByFollowersCount('desc')`.

**Example:**

```php
// Top 10 most followed users
$topUsers = User::orderByFollowersCountDesc()
    ->take(10)
    ->get();

// Most followed posts
$popularPosts = Post::orderByFollowersCountDesc()
    ->where('published', true)
    ->take(5)
    ->get();

// Weekly leaderboard
$weeklyTop = User::where('created_at', '>=', now()->subWeek())
    ->orderByFollowersCountDesc()
    ->take(20)
    ->get();
```

### `orderByFollowersCountAsc()`

Order models by follower count in ascending order (least followed first). Shortcut for `orderByFollowersCount('asc')`.

**Example:**

```php
// Users with fewest followers
$newUsers = User::orderByFollowersCountAsc()
    ->where('created_at', '>=', now()->subDays(30))
    ->get();

// Undiscovered content
$undiscoveredPosts = Post::orderByFollowersCountAsc()
    ->where('quality_score', '>', 7)
    ->take(10)
    ->get();
```

## Combining Scopes

Scopes can be chained together for complex queries:

```php
$user = User::find(1);
$follower = User::find(2);

// Check if specific follow exists and is accepted
$hasAcceptedFollow = Followable::of($user)
    ->followedBy($follower)
    ->accepted()
    ->exists();

// Get all pending user-to-user follows
$pendingUserFollows = Followable::withType(User::class)
    ->notAccepted()
    ->with(['user', 'followable'])
    ->get();

// Get accepted follows created this week
$weeklyAccepted = Followable::accepted()
    ->where('created_at', '>=', now()->subWeek())
    ->get();

// Most followed users who joined this month
$trendingNewUsers = User::where('created_at', '>=', now()->subMonth())
    ->orderByFollowersCountDesc()
    ->take(10)
    ->get();
```

## Practical Examples

### Popular Users Widget

```php
public function popularUsers()
{
    return User::orderByFollowersCountDesc()
        ->where('is_active', true)
        ->take(5)
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'followers_count' => $user->followers_count,
            ];
        });
}
```

### Trending Content

```php
public function trending()
{
    // Get posts with most follows in last 7 days
    $trendingPostIds = Followable::withType(Post::class)
        ->accepted()
        ->where('created_at', '>=', now()->subDays(7))
        ->select('followable_id', DB::raw('count(*) as follows_count'))
        ->groupBy('followable_id')
        ->orderByDesc('follows_count')
        ->limit(10)
        ->pluck('followable_id');
    
    return Post::whereIn('id', $trendingPostIds)
        ->with('author')
        ->get();
}
```

### Pending Requests Dashboard

```php
public function pendingRequests()
{
    $user = auth()->user();
    
    // Get all pending follow requests
    $requests = Followable::of($user)
        ->notAccepted()
        ->with('user.profile')
        ->latest('created_at')
        ->paginate(20);
    
    // Count by timeframe
    $stats = [
        'today' => Followable::of($user)
            ->notAccepted()
            ->whereDate('created_at', today())
            ->count(),
        'this_week' => Followable::of($user)
            ->notAccepted()
            ->where('created_at', '>=', now()->subWeek())
            ->count(),
        'total' => Followable::of($user)
            ->notAccepted()
            ->count(),
    ];
    
    return view('follow-requests.index', compact('requests', 'stats'));
}
```

### User Discovery

```php
public function discover(Request $request)
{
    $currentUser = auth()->user();
    
    // Users not currently followed
    $users = User::whereNotIn('id', function ($query) use ($currentUser) {
        $query->select('followable_id')
            ->from('followables')
            ->where('user_id', $currentUser->id)
            ->where('followable_type', User::class);
    })
    ->where('id', '!=', $currentUser->id)
    ->orderByFollowersCountDesc()
    ->paginate(20);
    
    $currentUser->attachFollowStatus($users);
    
    return view('discover', compact('users'));
}
```

### Follow Activity Timeline

```php
public function activityTimeline(User $user)
{
    $follows = Followable::followedBy($user)
        ->accepted()
        ->with('followable')
        ->latest('accepted_at')
        ->paginate(20);
    
    return view('users.activity', compact('follows', 'user'));
}
```

### Follower Growth Analytics

```php
public function followerGrowth(User $user)
{
    // Daily follower counts for last 30 days
    $growth = Followable::of($user)
        ->accepted()
        ->where('accepted_at', '>=', now()->subDays(30))
        ->selectRaw('DATE(accepted_at) as date, count(*) as count')
        ->groupBy('date')
        ->orderBy('date')
        ->get();
    
    return response()->json($growth);
}
```

### Mutual Followers

```php
public function mutualFollowers(User $user1, User $user2)
{
    $user1Followers = Followable::of($user1)
        ->accepted()
        ->pluck('user_id');
    
    $user2Followers = Followable::of($user2)
        ->accepted()
        ->pluck('user_id');
    
    $mutualIds = $user1Followers->intersect($user2Followers);
    
    return User::whereIn('id', $mutualIds)->get();
}
```

### Follow Suggestions

```php
public function suggestions(User $user)
{
    // Get IDs of users that people you follow also follow
    $followingIds = Followable::followedBy($user)
        ->where('followable_type', User::class)
        ->accepted()
        ->pluck('followable_id');
    
    $suggestedIds = Followable::whereIn('user_id', $followingIds)
        ->where('followable_type', User::class)
        ->accepted()
        ->whereNotIn('followable_id', function ($query) use ($user) {
            $query->select('followable_id')
                ->from('followables')
                ->where('user_id', $user->id)
                ->where('followable_type', User::class);
        })
        ->select('followable_id', DB::raw('count(*) as common_count'))
        ->groupBy('followable_id')
        ->orderByDesc('common_count')
        ->limit(10)
        ->pluck('followable_id');
    
    $suggestions = User::whereIn('id', $suggestedIds)->get();
    $user->attachFollowStatus($suggestions);
    
    return $suggestions;
}
```

### Statistics Dashboard

```php
public function statistics()
{
    return [
        'total_follows' => Followable::count(),
        'accepted_follows' => Followable::accepted()->count(),
        'pending_follows' => Followable::notAccepted()->count(),
        'follows_today' => Followable::whereDate('created_at', today())->count(),
        'follows_this_week' => Followable::where('created_at', '>=', now()->subWeek())->count(),
        'follows_this_month' => Followable::where('created_at', '>=', now()->subMonth())->count(),
        'most_followed_user' => User::orderByFollowersCountDesc()->first(),
        'most_active_follower' => User::withCount('followings')
            ->orderByDesc('followings_count')
            ->first(),
    ];
}
```

---

**Previous:** [Attach Follow Status](11-attach-follow-status.md) | **Next:** [Relationships](13-relationships.md)
