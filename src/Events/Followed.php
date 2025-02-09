<?php

declare(strict_types=1);

namespace Akira\Followable\Events;

use Akira\Followable\Followable;
use Illuminate\Foundation\Events\Dispatchable;

final class Followed
{
    use Dispatchable;

    public function __construct(public Followable $followable) {}
}
