# Laravel Followable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/akira/laravel-followable.svg)](https://packagist.org/packages/akira/laravel-followable)
[![Total Downloads](https://img.shields.io/packagist/dt/akira/laravel-followable.svg)](https://packagist.org/packages/akira/laravel-followable)
[![PHPStan Level](https://img.shields.io/badge/phpstan-level%209-brightgreen.svg)](https://phpstan.org)
[![License](https://img.shields.io/packagist/l/akira/laravel-followable.svg)](https://github.com/akira-io/laravel-followable/blob/main/LICENSE)

**Laravel Followable** is a lightweight and flexible Laravel package that adds follow/unfollow functionality to Eloquent
models. With an intuitive API, it allows users to follow other users, track entities, and manage relationships
effortlessly.

## Features

- **Follow/Unfollow Any Model** - Users can follow users, posts, channels, or any Eloquent model
- **Private Accounts** - Built-in approval workflow for follow requests
- **Polymorphic Relationships** - Follow different types of entities seamlessly
- **Query Scopes** - Powerful scopes for filtering and ordering by followers
- **Events** - Listen to `Followed` and `UnFollowed` events
- **Attach Follow Status** - Efficiently add follow status to collections
- **Type-Safe** - PHPStan Level 9 with 100% type coverage
- **Performance** - Optimized queries with eager loading support
- **Well Tested** - Comprehensive test suite with Pest PHP

## Requirements

- PHP 8.3+
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require akira/laravel-followable
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="followable-migrations"
php artisan migrate
```

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag="followable-config"
```

## Quick Start

### 1. Add Traits to Your Models

Add the `Follower` and `Followable` traits to your User model:

```php
use Akira\Followable\Concerns\Followable;
use Akira\Followable\Concerns\Follower;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Followable, Follower;
}
```

### 2. Follow Users

```php
$user = User::find(1);
$targetUser = User::find(2);

// Follow a user
$user->follow($targetUser);

// Unfollow a user
$user->unfollow($targetUser);

// Toggle follow
$user->toggleFollow($targetUser);
```

### 3. Check Follow Status

```php
// Check if following
if ($user->isFollowing($targetUser)) {
    echo "You are following this user";
}

// Check if followed by
if ($targetUser->isFollowedBy($user)) {
    echo "This user follows you";
}
```

### 4. Get Followers and Following

```php
// Get all followers
$followers = $user->followers;

// Get all following
$following = $user->followings;

// Count followers
$followersCount = $user->followers()->count();

// Get with pagination
$followers = $user->followers()->paginate(20);
```

## Private Accounts & Approval Workflow

Enable follow request approval by overriding the `needsToApproveFollowRequests()` method:

```php
class User extends Authenticatable
{
    use Followable, Follower;
    
    public function needsToApproveFollowRequests(): bool
    {
        return $this->is_private;
    }
}
```

Manage follow requests:

```php
// Check if request is pending
if ($user->hasRequestedToFollow($privateUser)) {
    echo "Your follow request is pending approval";
}

// Accept a follow request
$privateUser->acceptFollowRequestFrom($user);

// Reject a follow request
$privateUser->rejectFollowRequestFrom($user);

// Get pending requests
$pendingFollowers = $user->notApprovedFollowers;

// Get approved followers
$approvedFollowers = $user->approvedFollowers;
```

## Query Scopes

Order users by follower count:

```php
// Most followed users
$popularUsers = User::orderByFollowersCountDesc()->take(10)->get();

// Least followed users
$newUsers = User::orderByFollowersCountAsc()->take(10)->get();

// With additional filters
$topActiveUsers = User::where('is_active', true)
    ->orderByFollowersCountDesc()
    ->paginate(20);
```

## Events

Listen to follow/unfollow events:

```php
use Akira\Followable\Events\Followed;
use Akira\Followable\Events\UnFollowed;

// In EventServiceProvider
protected $listen = [
    Followed::class => [
        SendFollowNotification::class,
    ],
    UnFollowed::class => [
        RemoveFollowNotification::class,
    ],
];
```

## Attach Follow Status

Efficiently add follow status to collections:

```php
$users = User::all();
auth()->user()->attachFollowStatus($users);

foreach ($users as $user) {
    if ($user->has_followed) {
        echo "Following since {$user->followed_at->diffForHumans()}";
    }
}
```

## Documentation

Comprehensive documentation is available in the [`/docs`](docs) directory:

- [Installation Guide](docs/01-installation.md)
- [Configuration](docs/02-configuration.md)
- [Basic Usage](docs/04-basic-usage.md)
- [Follower Trait](docs/05-follower-trait.md)
- [Followable Trait](docs/06-followable-trait.md)
- [Approval Workflow](docs/07-approval-workflow.md)
- [Events](docs/09-events.md)
- [Query Scopes](docs/12-query-scopes.md)
- [Advanced Usage](docs/14-advanced-usage.md)
- [Testing](docs/15-testing.md)
- [API Reference](docs/17-api-reference.md)

**[ View Full Documentation](docs/README.md)**

## Testing

Run the test suite:

```bash
composer test
```

Run specific tests:

```bash
# Code formatting
composer test:lint

# Static analysis
composer test:types

# Type coverage
composer test:type-coverage

# Unit tests with coverage
composer test:coverage
```

## Configuration

The package can be configured via the `config/followable.php` file:

```php
return [
    // Use UUIDs instead of auto-incrementing IDs
    'uuids' => false,

    // Custom user foreign key column name
    'user_foreign_key' => 'user_id',

    // Custom table name
    'followables_table' => 'followables',

    // Custom model class
    'followables_model' => \Akira\Followable\Followable::class,
];
```

## Security

If you discover any security-related issues, please email kidiatoliny@akira-io.com instead of using the issue tracker.

Please see [SECURITY.md](SECURITY.md) for our security policy.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on:

- Development setup
- Coding standards (PSR-12, PHPStan Level 9)
- Testing requirements
- Pull request process

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [kidiatoliny](https://github.com/kidiatoliny)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
