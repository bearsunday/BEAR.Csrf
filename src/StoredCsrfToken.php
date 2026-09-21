<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use Override;

use function bin2hex;
use function hash_equals;
use function random_bytes;

/**
 * The synchroniser token itself: mint once per session, compare in constant time.
 *
 * The only class here that knows how a token is made or compared. Storage is
 * delegated to {@see CsrfStoreInterface} so that a host needing somewhere else
 * to put the string does not have to reimplement `random_bytes()` and
 * `hash_equals()` to get it.
 */
final class StoredCsrfToken implements CsrfTokenInterface
{
    public function __construct(private readonly CsrfStoreInterface $store)
    {
    }

    #[Override]
    public function issue(): string
    {
        $existing = $this->store->get();
        if ($existing !== null) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $this->store->set($token);

        return $token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        $stored = $this->store->get();
        if ($stored === null || $candidate === '') {
            return false;
        }

        return hash_equals($stored, $candidate);
    }

    #[Override]
    public function clear(): void
    {
        $this->store->remove();
    }
}
