<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Resource\AbstractUri;

final class FakeUri extends AbstractUri
{
    public function __construct()
    {
        $this->scheme = 'page';
        $this->host = 'self';
        $this->path = '/test';
    }
}
