# Advanced Usage

This guide covers advanced patterns and techniques for using Laravel Followable in complex scenarios.

## Polymorphic Follows

Follow different types of models:

```php
// Users following users
$user->follow($anotherUser);

// Users following posts
$user->follow($post);

// Users following channels
$user->follow($channel);

// Query follows by type
$userFollows = $user->followings()->withType(User::class)->get();
$postFollows = $user->followings()->withType(Post::class)->get();
```

## Batch Operations

### Follow Multiple Entities

```php
public function followMany(array $users)
{
    foreach ($users as $user) {
        try {
            $this->follow($user);
        } catch (\Exception $e) {
            logger()->error("Failed to follow user {$user->id}: {$e->getMessage()}");
        }
    }
}
```

### Unfollow Multiple Entities

```php
public function unfollowMany(array $users)
{
    foreach ($users as $user) {
        $this->unfollow($user);
    }
}
```

## Custom Follow Logic

### Conditional Auto-Approval

```php
public function needsToApproveFollowRequests(): bool
{
    $follower = auth()->user();
    
    // Auto-approve mutual connections
    if ($this->isFollowing($follower)) {
        return false;
    }
    
    // Auto-approve verified users
    if ($follower->is_verified) {
        return false;
    }
    
    return $this->is_private;
}
```

### Follow Limits

```php
public function follow(Model $followable): array
{
    // Check daily limit
    $todayFollows = $this->followings()
        ->whereDate('created_at', today())
        ->count();
    
    if ($todayFollows >= 100) {
        throw new \Exception('Daily follow limit reached');
    }
    
    return parent::follow($followable);
}
```

## Performance Optimization

### Cache Follower Counts

```php
use Illuminate\Support\Facades\Cache;

public function getCachedFollowersCount()
{
    return Cache::remember(
        "user.{$this->id}.followers_count",
        now()->addHours(24),
        fn () => $this->followers()->count()
    );
}

// Clear cache on follow/unfollow
Event::listen(Followed::class, function ($event) {
    $followable = $event->follow->followable;
    Cache::forget("user.{$followable->id}.followers_count");
});
```

### Eager Loading

```php
$users = User::with([
    'followers' => fn($q) => $q->limit(5),
    'followings' => fn($q) => $q->limit(5),
])->get();
```

## Integration Examples

### Nova Resource

```php
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\BelongsToMany;

class User extends Resource
{
    public function fields(Request $request)
    {
        return [
            Number::make('Followers', function () {
                return $this->followers()->count();
            }),
            
            BelongsToMany::make('Followers', 'followers', User::class),
            BelongsToMany::make('Following', 'followings', User::class),
        ];
    }
}
```

### Filament Resource

```php
use Filament\Tables\Columns\TextColumn;

public static function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('name'),
        TextColumn::make('followers_count')
            ->counts('followers')
            ->sortable(),
    ]);
}
```

---

**Previous:** [Relationships](13-relationships.md) | **Next:** [Testing](15-testing.md)
