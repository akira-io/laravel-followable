# Laravel Followable Documentation

Welcome to the Laravel Followable documentation. This guide will help you understand and implement follow/unfollow functionality in your Laravel application.

## Table of Contents

1. [Roadmap](00-roadmap.md) - Future features and improvements
2. [Installation](01-installation.md) - Getting started with the package
3. [Configuration](02-configuration.md) - Configuring the package
4. [Database Schema](03-database-schema.md) - Understanding the database structure
5. [Basic Usage](04-basic-usage.md) - Follow, unfollow, and basic operations
6. [Follower Trait](05-follower-trait.md) - Methods for models that can follow
7. [Followable Trait](06-followable-trait.md) - Methods for models that can be followed
8. [Approval Workflow](07-approval-workflow.md) - Managing follow requests
9. [Followable Model](08-followable-model.md) - The pivot model
10. [Events](09-events.md) - Listening to follow/unfollow events
11. [Exceptions](10-exceptions.md) - Handling errors
12. [Attach Follow Status](11-attach-follow-status.md) - Enriching collections with follow data
13. [Query Scopes](12-query-scopes.md) - Available query scopes
14. [Relationships](13-relationships.md) - Eloquent relationships
15. [Advanced Usage](14-advanced-usage.md) - Complex scenarios and patterns
16. [Testing](15-testing.md) - Testing your implementation
17. [Extending](16-extending.md) - Customizing the package
18. [API Reference](17-api-reference.md) - Complete API documentation

## Quick Start

```bash
# Install
composer require akira/laravel-followable

# Publish and run migrations
php artisan vendor:publish --tag="followable-migrations"
php artisan migrate

# Add traits to your User model
use Akira\Followable\Concerns\Followable;
use Akira\Followable\Concerns\Follower;

class User extends Model
{
    use Followable, Follower;
}

# Start using
$user->follow($anotherUser);
$user->isFollowing($anotherUser); // true
```

## Features

- ✅ Follow/unfollow users and any Eloquent model
- ✅ Approval workflow for private accounts
- ✅ Polymorphic relationships
- ✅ Query scopes for filtering and ordering
- ✅ Events for follow/unfollow actions
- ✅ Attach follow status to collections
- ✅ Type-safe with PHPStan Level 9
- ✅ 100% type coverage
- ✅ Comprehensive test suite

## Support

- **GitHub Issues:** [https://github.com/akira-io/laravel-followable/issues](https://github.com/akira-io/laravel-followable/issues)
- **Documentation:** This directory
- **Website:** [https://followable.akira-io.com](https://followable.akira-io.com)

## Contributing

See [CONTRIBUTING.md](../CONTRIBUTING.md) for details on how to contribute.

## Security

See [SECURITY.md](../SECURITY.md) for our security policy and how to report vulnerabilities.

## License

Laravel Followable is open-sourced software licensed under the [MIT license](../LICENSE.md).
