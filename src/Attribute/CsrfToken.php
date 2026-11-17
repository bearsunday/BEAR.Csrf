<?php

declare(strict_types=1);

namespace BEAR\Csrf\Attribute;

use Attribute;

/**
 * Rejects the request unless its CSRF token matches the one stored in the session
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/csrf.html#resource-attributes
 * @see \BEAR\Csrf\Interceptor\CsrfTokenInterceptor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class CsrfToken
{
}
