<?php

declare(strict_types=1);

namespace BEAR\Csrf\Exception;

use Throwable;

final class MissingCsrfTokenForbiddenException extends ForbiddenException
{
    public function __construct(Throwable|null $previous = null)
    {
        parent::__construct('CSRF token missing.', $previous);
    }
}
