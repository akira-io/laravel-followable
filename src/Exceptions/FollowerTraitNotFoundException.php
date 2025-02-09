<?php

declare(strict_types=1);

namespace Akira\Followable\Exceptions;

use Exception;

final class FollowerTraitNotFoundException extends Exception
{
    /**
     * Create a new FollowerTraitNotFoundException instance.
     */
    public function __construct()
    {
        parent::__construct(__('The follower model must use the Follower trait.'));
    }
}
