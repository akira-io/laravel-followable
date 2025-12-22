# Events

Laravel Followable dispatches events when users follow and unfollow entities. This guide explains how to listen to and handle these events.

## Available Events

### `Followed`

Dispatched when a follow relationship is created.

**Class:** `Akira\Followable\Events\Followed`

**Triggered:** When the `Followable` model's `created` event fires

**Properties:**
- `follow` (`Followable`) - The follow model instance

### `UnFollowed`

Dispatched when a follow relationship is deleted.

**Class:** `Akira\Followable\Events\UnFollowed`

**Triggered:** When the `Followable` model's `deleted` event fires

**Properties:**
- `unfollow` (`Followable`) - The follow model instance

## Event Structure

Both events follow a simple structure:

```php
namespace Akira\Followable\Events;

use Akira\Followable\Followable;
use Illuminate\Foundation\Events\Dispatchable;

final class Followed
{
    use Dispatchable;

    public function __construct(public Followable $follow) {}
}
```

## Listening to Events

### Register Event Listeners

In your `EventServiceProvider`:

```php
namespace App\Providers;

use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;
use App\Listeners\SendFollowNotification;
use App\Listeners\UpdateFollowerCount;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Followed::class => [
            SendFollowNotification::class,
            UpdateFollowerCount::class,
        ],
        UnFollowed::class => [
            UpdateFollowerCount::class,
        ],
    ];
}
```

### Create Listeners

```bash
php artisan make:listener SendFollowNotification
php artisan make:listener UpdateFollowerCount
```

## Accessing Event Data

### The Follow Property

Both events contain a `follow` (or `unfollow`) property that gives access to the full relationship:

```php
use Akira\Followable\Events\Followed;

class SendFollowNotification
{
    public function handle(Followed $event): void
    {
        $follow = $event->follow;
        
        // Get the follower (user who followed)
        $follower = $follow->user; // or $follow->follower
        
        // Get the followable (entity being followed)
        $followable = $follow->followable;
        
        // Get timestamps
        $followedAt = $follow->created_at;
        $acceptedAt = $follow->accepted_at; // null if pending
        
        // Check if follow is pending
        $isPending = is_null($follow->accepted_at);
    }
}
```

## Practical Examples

### Send Notification on Follow

```php
namespace App\Listeners;

use Akira\Followable\Events\Followed;
use App\Notifications\NewFollowerNotification;
use App\Notifications\FollowRequestNotification;

class SendFollowNotification
{
    public function handle(Followed $event): void
    {
        $follow = $event->follow;
        $follower = $follow->user;
        $followable = $follow->followable;
        
        // Check if notification should be sent
        if (!$this->shouldNotify($followable)) {
            return;
        }
        
        // Send different notification based on approval status
        if ($follow->accepted_at) {
            // Follow was accepted immediately
            $followable->notify(new NewFollowerNotification($follower));
        } else {
            // Follow is pending approval
            $followable->notify(new FollowRequestNotification($follower));
        }
    }
    
    private function shouldNotify($followable): bool
    {
        // Only notify users, not other entity types
        if (!$followable instanceof \App\Models\User) {
            return false;
        }
        
        // Check user notification preferences
        return $followable->settings->notify_on_follow ?? true;
    }
}
```

### Update Cache on Follow/Unfollow

```php
namespace App\Listeners;

use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;
use Illuminate\Support\Facades\Cache;

class UpdateFollowerCount
{
    public function handle(Followed|UnFollowed $event): void
    {
        $follow = $event->follow ?? $event->unfollow;
        $followable = $follow->followable;
        
        // Clear cache for follower counts
        $cacheKey = "user.{$followable->id}.followers_count";
        Cache::forget($cacheKey);
        
        // Optionally recalculate and cache
        $count = $followable->followers()->count();
        Cache::put($cacheKey, $count, now()->addHours(24));
    }
}
```

### Track Follow Analytics

```php
namespace App\Listeners;

use Akira\Followable\Events\Followed;
use App\Models\FollowAnalytic;

class TrackFollowAnalytics
{
    public function handle(Followed $event): void
    {
        $follow = $event->follow;
        
        FollowAnalytic::create([
            'follower_id' => $follow->user_id,
            'followable_id' => $follow->followable_id,
            'followable_type' => $follow->followable_type,
            'is_pending' => is_null($follow->accepted_at),
            'followed_at' => $follow->created_at,
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]);
    }
}
```

### Queue Follow Notifications

For better performance, queue notification sending:

```php
namespace App\Listeners;

use Akira\Followable\Events\Followed;
use App\Jobs\SendFollowNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFollowNotification implements ShouldQueue
{
    public $queue = 'notifications';
    
    public function handle(Followed $event): void
    {
        SendFollowNotificationJob::dispatch($event->follow);
    }
}
```

### Trigger External Webhooks

```php
namespace App\Listeners;

use Akira\Followable\Events\Followed;
use Illuminate\Support\Facades\Http;

class TriggerFollowWebhook
{
    public function handle(Followed $event): void
    {
        $follow = $event->follow;
        
        $webhookUrl = config('services.webhook.follow_url');
        
        if (!$webhookUrl) {
            return;
        }
        
        Http::post($webhookUrl, [
            'event' => 'user.followed',
            'follower_id' => $follow->user_id,
            'followable_id' => $follow->followable_id,
            'followable_type' => $follow->followable_type,
            'pending' => is_null($follow->accepted_at),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

## Using Closures

For simple use cases, you can listen to events using closures in your `EventServiceProvider`:

```php
use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(Followed::class, function (Followed $event) {
        $follow = $event->follow;
        
        // Simple logging
        logger()->info('User followed', [
            'follower_id' => $follow->user_id,
            'followable_id' => $follow->followable_id,
            'followable_type' => $follow->followable_type,
        ]);
    });
    
    Event::listen(UnFollowed::class, function (UnFollowed $event) {
        $unfollow = $event->unfollow;
        
        logger()->info('User unfollowed', [
            'follower_id' => $unfollow->user_id,
            'followable_id' => $unfollow->followable_id,
        ]);
    });
}
```

## Testing with Events

### Fake Events in Tests

```php
use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;
use Illuminate\Support\Facades\Event;

test('follow dispatches followed event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $target = User::factory()->create();
    
    $user->follow($target);
    
    Event::assertDispatched(Followed::class, function ($event) use ($user, $target) {
        return $event->follow->user_id === $user->id
            && $event->follow->followable_id === $target->id;
    });
});

test('unfollow dispatches unfollowed event', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $target = User::factory()->create();
    $user->follow($target);
    
    $user->unfollow($target);
    
    Event::assertDispatched(UnFollowed::class);
});
```

### Assert Event Data

```php
test('followed event contains correct data', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $target = User::factory()->create();
    
    $user->follow($target);
    
    Event::assertDispatched(Followed::class, function ($event) use ($user, $target) {
        $follow = $event->follow;
        
        expect($follow->user)->toBeInstanceOf(User::class)
            ->and($follow->user->id)->toBe($user->id)
            ->and($follow->followable)->toBeInstanceOf(User::class)
            ->and($follow->followable->id)->toBe($target->id)
            ->and($follow->accepted_at)->not()->toBeNull();
        
        return true;
    });
});
```

## Event Subscribers

For complex event handling, create an event subscriber:

```php
namespace App\Listeners;

use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;
use Illuminate\Events\Dispatcher;

class FollowEventSubscriber
{
    public function handleFollowed(Followed $event): void
    {
        // Handle followed
    }
    
    public function handleUnfollowed(UnFollowed $event): void
    {
        // Handle unfollowed
    }
    
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            Followed::class,
            [FollowEventSubscriber::class, 'handleFollowed']
        );
        
        $events->listen(
            UnFollowed::class,
            [FollowEventSubscriber::class, 'handleUnfollowed']
        );
    }
}
```

Register the subscriber in `EventServiceProvider`:

```php
protected $subscribe = [
    FollowEventSubscriber::class,
];
```

## Performance Considerations

### Queue Heavy Listeners

For tasks that take time (API calls, emails, etc.), implement `ShouldQueue`:

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFollowNotification implements ShouldQueue
{
    public $queue = 'notifications';
    public $delay = 5; // seconds
    
    public function handle(Followed $event): void
    {
        // Heavy operation
    }
}
```

### Conditional Listener Execution

Only run listeners when necessary:

```php
class UpdateSearchIndex implements ShouldQueue
{
    public function handle(Followed $event): void
    {
        $followable = $event->follow->followable;
        
        // Only update search index for users
        if (!$followable instanceof User) {
            return;
        }
        
        // Update search index
        $followable->searchable();
    }
}
```

---

**Previous:** [Followable Model](08-followable-model.md) | **Next:** [Exceptions](10-exceptions.md)
