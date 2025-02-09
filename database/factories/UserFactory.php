<?php

declare(strict_types=1);

namespace Akira\Followable\Database\Factories;

use Akira\Followable\Tests\Fixtures\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @internal
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'is_private' => false,
        ];
    }

    public function isPrivate(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_private' => true,
            ];
        });
    }
}
