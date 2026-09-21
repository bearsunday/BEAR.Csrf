<?php

declare(strict_types=1);

namespace BEAR\Csrf\Exception;

use Throwable;

final class InvalidCsrfTokenForbiddenException extends ForbiddenException
{
    public function __construct(Throwable|null $previous = null)
    {
        parent::__construct('CSRF token invalid.', $previous);
    }
}
