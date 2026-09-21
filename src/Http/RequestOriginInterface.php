<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

interface RequestOriginInterface
{
    public function fetchSite(): string|null;

    public function origin(): string|null;

    public function referer(): string|null;
}
