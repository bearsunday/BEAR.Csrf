<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Resource\ResourceObject;
use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\Attribute\SameOrigin;

final class FakeResource extends ResourceObject
{
    public function onGet(): static
    {
        return $this;
    }

    #[CsrfToken]
    public function onPost(): static
    {
        return $this;
    }

    #[CsrfToken('custom_token')]
    public function onPut(): static
    {
        return $this;
    }

    #[SameOrigin]
    public function onDelete(): static
    {
        return $this;
    }

    #[SameOrigin]
    #[CsrfToken]
    public function onPatch(): static
    {
        return $this;
    }
}
