<?php

declare(strict_types=1);

namespace BEAR\Csrf\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class CsrfToken
{
    public function __construct(
        public string|null $field = null,
    ) {
    }
}
