<?php

declare(strict_types=1);

namespace Akira\Followable\Exceptions;

use Exception;

final class FollowableTraitNotFoundException extends Exception
{
    /**
     * Create a new FollowableTraitNotFoundException instance.
     */
    public function __construct()
    {
        parent::__construct(__('The followable model must use the Followable trait.'));
    }
}
