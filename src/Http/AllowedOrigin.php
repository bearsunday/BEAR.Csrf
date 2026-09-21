<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

final readonly class AllowedOrigin
{
    public function __construct(
        public string|null $value,
    ) {
    }
}
