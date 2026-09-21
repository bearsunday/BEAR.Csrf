<?php

declare(strict_types=1);

namespace BEAR\Csrf;

/**
 * Where the issued token is kept.
 *
 * Separate from {@see CsrfTokenInterface} so that changing the storage does
 * not mean rewriting the comparison. Everything security-sensitive — minting
 * with `random_bytes()`, comparing with `hash_equals()` — stays in
 * {@see StoredCsrfToken}; an implementation of this interface only answers
 * where the string lives.
 *
 * A host that serves several requests from one persistent worker cannot use
 * PHP's session for this, and implements the interface against something
 * scoped to the request instead: coroutine context, or a shared backend keyed
 * by the session id.
 */
interface CsrfStoreInterface
{
    /** @return non-empty-string|null The stored token, or null when none is stored. */
    public function get(): string|null;

    public function set(string $token): void;

    public function remove(): void;
}
