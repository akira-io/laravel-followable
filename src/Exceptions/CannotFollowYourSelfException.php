<?php

declare(strict_types=1);

namespace Akira\Followable\Exceptions;

use Exception;

final class CannotFollowYourSelfException extends Exception
{
    /**
     * Create a new CannotFollowYourSelfException instance.
     */
    public function __construct()
    {
        parent::__construct(__('You cannot follow yourself'));
    }
}
