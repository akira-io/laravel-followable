# Laravel Followable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/akira/laravel-followable.svg)](https://packagist.org/packages/akira/laravel-followable)
[![Total Downloads](https://img.shields.io/packagist/dt/akira/laravel-followable.svg)](https://packagist.org/packages/akira/laravel-followable)
[![PHPStan Level](https://img.shields.io/badge/phpstan-level%209-brightgreen.svg)](https://phpstan.org)
[![License](https://img.shields.io/packagist/l/akira/laravel-followable.svg)](https://github.com/akira-io/laravel-followable/blob/main/LICENSE)

**Laravel Followable** is a lightweight and flexible Laravel package that adds follow/unfollow functionality to Eloquent
models. With an intuitive API, it allows users to follow other users, track entities, and manage relationships
effortlessly.

## Installation

You can install the package via composer:

```bash
composer require akira-io/laravel-followable
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="followable-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="followable-config"
```

## Documentation

You'll find installation instructions and full documentation on [Followable website](https://followable.akira-io.com).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [kidiatoliny](https://github.com/kidiatoliny)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
