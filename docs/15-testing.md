# Testing

This guide explains how to test follow/unfollow functionality in your Laravel application using the features from Laravel Followable.

## Basic Test Examples

### Testing Follow Functionality

```php
use Akira\Followable\Events\Followed;
use Illuminate\Support\Facades\Event;

test('user can follow another user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();
    
    $user->follow($target);
    
    expect($user->isFollowing($target))->toBeTrue()
        ->and($target->isFollowedBy($user))->toBeTrue();
});

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
```

### Testing Unfollow Functionality

```php
use Akira\Followable\Events\UnFollowed;

test('user can unfollow another user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();
    $user->follow($target);
    
    $user->unfollow($target);
    
    expect($user->isFollowing($target))->toBeFalse()
        ->and($target->isFollowedBy($user))->toBeFalse()
        ->and($user->followings)->toHaveCount(0);
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

### Testing Approval Workflow

```php
test('private users require approval', function () {
    $publicUser = User::factory()->create(['is_private' => false]);
    $privateUser = User::factory()->create(['is_private' => true]);
    $follower = User::factory()->create();
    
    // Public user - immediate accept
    $result = $follower->follow($publicUser);
    expect($result['pending'])->toBeFalse();
    
    // Private user - pending
    $result = $follower->follow($privateUser);
    expect($result['pending'])->toBeTrue();
});

test('should accept pending requests', function () {
    $user = User::factory()->isPrivate()->create();
    $follower = User::factory()->create();
    
    $follower->follow($user);
    $user->acceptFollowRequestFrom($follower);
    
    expect($follower->isFollowing($user))->toBeTrue()
        ->and($user->isFollowedBy($follower))->toBeTrue();
});

test('should reject pending requests', function () {
    $user = User::factory()->isPrivate()->create();
    $follower = User::factory()->create();
    
    $follower->follow($user);
    $user->rejectFollowRequestFrom($follower);
    
    expect($follower->isFollowing($user))->toBeFalse()
        ->and($user->isFollowedBy($follower))->toBeFalse();
});
```

### Testing Exceptions

```php
use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;

test('throws exception when following self', function () {
    $user = User::factory()->create();
    
    expect(fn() => $user->follow($user))
        ->toThrow(CannotFollowYourSelfException::class);
});

test('throws exception when following non-followable model', function () {
    $user = User::factory()->create();
    $nonFollowable = new class extends Model {};
    
    expect(fn() => $user->follow($nonFollowable))
        ->toThrow(FollowableTraitNotFoundException::class);
});
```

## Using Factories

The package tests use factories for the User model. You can create similar factories:

```php
// database/factories/UserFactory.php
public function isPrivate(): static
{
    return $this->state(fn (array $attributes) => [
        'is_private' => true,
    ]);
}
```

---

**Previous:** [Advanced Usage](14-advanced-usage.md) | **Next:** [Extending](16-extending.md)
