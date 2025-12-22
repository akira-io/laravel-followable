# Roadmap

This document outlines potential future features and improvements for Laravel Followable based on the current
architecture and extension points available in the package.

## Planned Features

### Enhanced Notification System

The package currently dispatches `Followed` and `UnFollowed` events. Future versions could integrate Laravel's
notification system to automatically notify users when:

- Someone follows them
- Their follow request is accepted
- Their follow request is rejected
- Someone they follow unfollows them

### Follow Request Notes

Allow users to add optional messages when requesting to follow private accounts:

```php
$user->follow($privateUser, message: 'Hi! I love your content');
```

### Blocking Functionality

Extend the current follow system to support blocking:

- Block users from following you
- Check if a user is blocked
- Automatically reject follow requests from blocked users
- List blocked users

### Statistics & Analytics

Provide built-in methods for common follow-related statistics:

- Most followed users in a given timeframe
- Follow/unfollow rate calculations
- Follower growth metrics
- Mutual follows detection
- Follow suggestions based on common connections

### Batch Operations

Optimize performance for bulk follow operations:

```php
$user->followMany([$user1, $user2, $user3]);
$user->unfollowMany([$user1, $user2]);
```

### Follow Categories/Lists

Allow organizing follows into categories:

- Create custom lists (e.g., "Close Friends", "Family")
- Add followers to specific lists
- Query follows by category

### Webhook Support

Integrate webhook functionality to notify external services when follow events occur, useful for:

- Third-party integrations
- Microservice architectures
- Real-time dashboards

### Activity Streams

Generate activity streams based on follow relationships:

- "Users you follow" feed
- "Suggested content" based on what people you follow interact with
- Follow timeline

### Soft Deletes Support

Add soft delete support to the `followables` table to:

- Keep historical follow data
- Restore accidentally deleted follows
- Analyze follow/unfollow patterns

### Rate Limiting

Built-in rate limiting for follow actions to prevent abuse:

- Limit follows per hour/day
- Configurable throttling
- Anti-spam measures

### Follower Quality Metrics

Provide methods to assess follower quality:

- Detect suspicious follow patterns
- Identify bot-like behavior
- Calculate engagement scores

### Multi-tenancy Support

Enhance the package to work seamlessly in multi-tenant applications:

- Tenant-scoped relationships
- Isolated follow graphs per tenant
- Shared user follows across tenants (optional)

### Import/Export Tools

Artisan commands for:

- Exporting follow data
- Importing follows from external sources
- Backup and restore capabilities

### GraphQL API Support

Provide pre-built GraphQL types and resolvers for:

- Querying followers/following
- Follow mutations
- Subscription support for real-time updates

### Custom Follow Types

Support different types of follows beyond binary follow/unfollow:

- "Close Friends" with elevated privileges
- "Muted" follows (follow but hide content)
- Follow with notification preferences

### API for Third-party Authentication

Allow follows to be synchronized with external platforms:

- Twitter/X follows
- Instagram follows
- LinkedIn connections

## Extension Points

The current architecture provides several extension points for custom implementations:

### Custom Followable Model

The `followables_model` configuration key allows you to use a custom model extending the base `Followable` class,
enabling:

- Custom attributes and business logic
- Additional relationships
- Custom query scopes
- Integration with other packages

### Overridable Methods

The `needsToApproveFollowRequests()` method can be overridden to implement complex approval logic:

- Time-based approval (auto-approve certain users)
- Reputation-based approval
- Mutual connection requirements
- Third-party verification integration

### Event System Integration

The existing `Followed` and `UnFollowed` events can be extended or used to trigger:

- Queue jobs for heavy processing
- Cache invalidation
- External API calls
- Analytics tracking

### Polymorphic Relationships

The morphable nature of the follow system allows tracking follows on any model, enabling:

- Following posts, articles, or topics
- Following locations or tags
- Following events or projects
- Complex follow graphs

## Community Feedback

We welcome feature requests and suggestions from the community. Please open an issue on our GitHub repository to discuss
potential features or submit a pull request if you've implemented something useful.

---
**Next:** [Installation](01-installation.md)
