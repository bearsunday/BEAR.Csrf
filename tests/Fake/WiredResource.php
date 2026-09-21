<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Resource\ResourceObject;
use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\Attribute\SameOrigin;

class WiredResource extends ResourceObject
{
    #[CsrfToken]
    public function onPost(): static
    {
        $this->body = ['result' => 'ok'];

        return $this;
    }

    #[SameOrigin]
    public function onDelete(): static
    {
        $this->body = ['result' => 'ok'];

        return $this;
    }
}
