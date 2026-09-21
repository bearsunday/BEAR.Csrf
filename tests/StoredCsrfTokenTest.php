<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Fake\ArrayCsrfStore;
use PHPUnit\Framework\TestCase;

use function strlen;

final class StoredCsrfTokenTest extends TestCase
{
    public function testIssueMintsATokenAndKeepsIt(): void
    {
        $store = new ArrayCsrfStore();
        $csrf = new StoredCsrfToken($store);

        $token = $csrf->issue();

        $this->assertSame(64, strlen($token));
        $this->assertSame($token, $store->get());
    }

    /** One token per session: a second form must not invalidate the first. */
    public function testIssueReturnsTheStoredTokenWhenOneExists(): void
    {
        $csrf = new StoredCsrfToken(new ArrayCsrfStore('already-issued'));

        $this->assertSame('already-issued', $csrf->issue());
    }

    public function testVerifyAcceptsTheStoredToken(): void
    {
        $this->assertTrue((new StoredCsrfToken(new ArrayCsrfStore('a-token')))->verify('a-token'));
    }

    public function testVerifyRejectsADifferentToken(): void
    {
        $this->assertFalse((new StoredCsrfToken(new ArrayCsrfStore('a-token')))->verify('forged'));
    }

    public function testVerifyRejectsWhenNothingIsStored(): void
    {
        $this->assertFalse((new StoredCsrfToken(new ArrayCsrfStore()))->verify('a-token'));
    }

    /**
     * An empty candidate is a mismatch, never a match. A missing token arrives here as '',
     * so treating it as equal to an empty store would accept every unprotected request.
     */
    public function testVerifyRejectsAnEmptyCandidate(): void
    {
        $this->assertFalse((new StoredCsrfToken(new ArrayCsrfStore('a-token')))->verify(''));
        $this->assertFalse((new StoredCsrfToken(new ArrayCsrfStore()))->verify(''));
    }

    public function testClearRemovesTheToken(): void
    {
        $store = new ArrayCsrfStore('a-token');

        (new StoredCsrfToken($store))->clear();

        $this->assertNull($store->get());
    }

    public function testTokensAreNotPredictable(): void
    {
        $first = (new StoredCsrfToken(new ArrayCsrfStore()))->issue();
        $second = (new StoredCsrfToken(new ArrayCsrfStore()))->issue();

        $this->assertNotSame($first, $second);
    }
}
