<?php

declare(strict_types=1);

namespace BEAR\Csrf\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use Throwable;

class ForbiddenException extends BadRequestException
{
    public function __construct(string $message = 'Forbidden.', Throwable|null $previous = null)
    {
        parent::__construct($message, Code::FORBIDDEN, $previous);
    }
}
