<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Csrf\CsrfStoreInterface;
use Override;

/** Storage with no session behind it, so token logic can be tested on its own. */
final class ArrayCsrfStore implements CsrfStoreInterface
{
    public function __construct(private string|null $token = null)
    {
    }

    /** @return non-empty-string|null */
    #[Override]
    public function get(): string|null
    {
        return $this->token === '' ? null : $this->token;
    }

    #[Override]
    public function set(string $token): void
    {
        $this->token = $token;
    }

    #[Override]
    public function remove(): void
    {
        $this->token = null;
    }
}
