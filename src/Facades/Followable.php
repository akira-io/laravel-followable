<?php

declare(strict_types=1);

namespace Akira\Followable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Akira\Followable\Followable
 */
final class Followable extends Facade
{
    /**
     * Get the facade accessor.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Followable\Followable::class;
    }
}
