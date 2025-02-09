<?php

declare(strict_types=1);

namespace Akira\Followable\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Akira\Followable\Followable
 */
final class Followable extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Followable\Followable::class;
    }
}
