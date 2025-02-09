<?php

declare(strict_types=1);

namespace Akira\Followable\Events;

use Akira\Followable\Followable;
use Illuminate\Foundation\Events\Dispatchable;

final class UnFollowed
{
    use Dispatchable;

    /**
     * Create a new UnFollowed instance.
     */
    public function __construct(public Followable $followable) {}
}
