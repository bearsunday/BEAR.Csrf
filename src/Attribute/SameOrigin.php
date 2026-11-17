<?php

declare(strict_types=1);

namespace BEAR\Csrf\Attribute;

use Attribute;

/**
 * Rejects a cross-origin request, judged by Sec-Fetch-Site, Origin or Referer
 *
 * @see https://bearsunday.github.io/manuals/1.0/en/csrf.html#resource-attributes
 * @see \BEAR\Csrf\Interceptor\SameOriginInterceptor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SameOrigin
{
}
