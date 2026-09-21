<?php

declare(strict_types=1);

namespace BEAR\Csrf\Exception;

use Throwable;

final class CrossOriginForbiddenException extends ForbiddenException
{
    public function __construct(string $message, Throwable|null $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
