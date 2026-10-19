<?php

declare(strict_types=1);

namespace BEAR\Csrf\Exception;

use RuntimeException;

/** PHP could not start the session the token is stored in. The PHP warning names the cause. */
final class SessionStartFailedException extends RuntimeException
{
}
