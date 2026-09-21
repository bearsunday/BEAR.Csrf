<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use InvalidArgumentException;
use Override;
use BEAR\Csrf\CsrfTokenInterface;

final class FakeCsrfToken implements CsrfTokenInterface
{
    /** @var non-empty-string */
    private string $token;

    public function __construct(string $token = 'valid-token')
    {
        if ($token === '') {
            throw new InvalidArgumentException('Fake CSRF token must not be empty.');
        }

        $this->token = $token;
    }

    #[Override]
    public function issue(): string
    {
        return $this->token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        return $candidate === $this->token;
    }

    #[Override]
    public function clear(): void
    {
        $this->token = 'cleared-token';
    }
}
