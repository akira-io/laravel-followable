# Contributing to Laravel Followable

Thank you for considering contributing to Laravel Followable! This document outlines the process for contributing to the project.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to kidiatoliny@akira-io.com.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples** (code samples, test cases)
- **Describe the behavior you observed** and what you expected
- **Include Laravel and PHP versions**
- **Include package version**

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:

- **Use a clear and descriptive title**
- **Provide a detailed description** of the proposed feature
- **Explain why this enhancement would be useful**
- **Include code examples** if applicable

### Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Write or update tests
5. Ensure all tests pass
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## Development Setup

### Prerequisites

- PHP 8.3 or higher
- Composer
- Laravel 11.x or 12.x

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/laravel-followable.git
cd laravel-followable

# Install dependencies
composer install

# Install Node dependencies (for commit linting)
npm install
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test suites
composer test:lint
composer test:refactor
composer test:types
composer test:coverage
composer test:typos

# Run Pest tests
vendor/bin/pest

# Run PHPStan
vendor/bin/phpstan analyse

# Run Pint (code formatting)
vendor/bin/pint
```

## Coding Standards

This project follows strict coding standards:

### PHP Standards

- **PSR-12** coding standard
- **Strict types** declaration in all PHP files
- **Type hints** for all parameters and return types
- **PHPStan Level 9** compliance
- **100% type coverage** required

### Code Style

We use Laravel Pint for code formatting:

```bash
# Format code
composer lint

# Check formatting
composer test:lint
```

### Static Analysis

All code must pass PHPStan Level 9:

```bash
composer test:types
```

### Testing Requirements

- All new features must include tests
- Bug fixes should include regression tests
- Maintain or improve code coverage (currently 50%)
- Tests must pass before merging

### Commit Messages

We use conventional commits for consistent commit messages:

```
feat: add ability to follow multiple models at once
fix: resolve N+1 query issue in attachFollowStatus
docs: update installation instructions
test: add tests for approval workflow
refactor: simplify follow logic
chore: update dependencies
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `test`: Adding or updating tests
- `refactor`: Code refactoring
- `chore`: Maintenance tasks
- `perf`: Performance improvements

## Testing Guidelines

### Writing Tests

Use Pest PHP for testing:

```php
test('user can follow another user', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();
    
    $user->follow($target);
    
    expect($user->isFollowing($target))->toBeTrue();
});
```

### Test Coverage

- Aim for meaningful test coverage
- Test happy paths and edge cases
- Test exception scenarios
- Test with different configurations (UUIDs, custom table names)

## Documentation

- Update relevant documentation for new features
- Include code examples in docblocks
- Add entries to appropriate documentation files in `/docs`
- Update README.md if necessary

## Review Process

1. All pull requests require review before merging
2. Address review feedback promptly
3. Keep pull requests focused and atomic
4. Ensure CI passes before requesting review

## Questions?

If you have questions about contributing, please open an issue or contact the maintainers at kidiatoliny@akira-io.com.

## License

By contributing to Laravel Followable, you agree that your contributions will be licensed under the MIT License.
