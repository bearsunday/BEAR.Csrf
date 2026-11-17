<?php

declare(strict_types=1);

namespace BEAR\Csrf\Attribute;

use Attribute;

/**
 * Rejects a request that is not same-origin: Sec-Fetch-Site decides when present, else Origin, else Referer
 *
 * Checks nothing while AllowedOrigin is null (CsrfModule::withoutSameOriginCheck())
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/csrf.html#resource-attributes
 * @see \BEAR\Csrf\Interceptor\SameOriginInterceptor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SameOrigin
{
}
