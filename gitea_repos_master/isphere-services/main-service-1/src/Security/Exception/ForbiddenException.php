<?php

declare(strict_types=1);

namespace App\Security\Exception;

class ForbiddenException extends \RuntimeException
{
    public function __construct(\Throwable $previous = null)
    {
        parent::__construct('Access denied', $previous);
    }
}
