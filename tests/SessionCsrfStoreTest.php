<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use PHPUnit\Framework\TestCase;

use function session_id;
use function session_start;
use function session_status;
use function session_write_close;

use const PHP_SESSION_ACTIVE;

final class SessionCsrfStoreTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            return;
        }

        session_id('bear-csrf-test');
        session_start();
        $_SESSION = [];
    }

    public function testRoundTrip(): void
    {
        $store = new SessionCsrfStore();

        $this->assertNull($store->get());

        $store->set('a-token');
        $this->assertSame('a-token', $store->get());

        $store->remove();
        $this->assertNull($store->get());
    }

    /** An empty string in the slot is no token, not a token that matches nothing. */
    public function testEmptyStoredValueReadsAsAbsent(): void
    {
        $_SESSION[CsrfSessionKey::DEFAULT_NAME] = '';

        $this->assertNull((new SessionCsrfStore())->get());
    }

    public function testNonStringStoredValueReadsAsAbsent(): void
    {
        $_SESSION[CsrfSessionKey::DEFAULT_NAME] = ['not', 'a', 'token'];

        $this->assertNull((new SessionCsrfStore())->get());
    }

    public function testStartsSessionWhenInactive(): void
    {
        session_write_close();
        $this->assertNotSame(PHP_SESSION_ACTIVE, session_status());

        (new SessionCsrfStore())->set('a-token');

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * Which slot holds the token is part of the contract: an application sharing a session
     * with an existing system has to name it, and interoperability breaks silently if the
     * key is fixed.
     */
    public function testTokenIsStoredUnderTheConfiguredKey(): void
    {
        (new SessionCsrfStore(new CsrfSessionKey('cms_token')))->set('a-token');

        $this->assertSame('a-token', $_SESSION['cms_token'] ?? null);
        $this->assertArrayNotHasKey(CsrfSessionKey::DEFAULT_NAME, $_SESSION);
    }

    public function testDefaultKeyIsUsedWhenNoneIsConfigured(): void
    {
        (new SessionCsrfStore())->set('a-token');

        $this->assertSame('a-token', $_SESSION[CsrfSessionKey::DEFAULT_NAME] ?? null);
    }

    public function testStoresDoNotSeeAcrossKeys(): void
    {
        (new SessionCsrfStore(new CsrfSessionKey('area_a')))->set('a-token');

        $this->assertNull((new SessionCsrfStore(new CsrfSessionKey('area_b')))->get());
    }

    public function testRemoveTouchesOnlyTheConfiguredKey(): void
    {
        $_SESSION['unrelated'] = 'keep';
        $store = new SessionCsrfStore(new CsrfSessionKey('cms_token'));
        $store->set('a-token');

        $store->remove();

        $this->assertArrayNotHasKey('cms_token', $_SESSION);
        $this->assertSame('keep', $_SESSION['unrelated']);
    }
}
