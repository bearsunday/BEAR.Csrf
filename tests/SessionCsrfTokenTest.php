<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use PHPUnit\Framework\TestCase;

use function session_id;
use function session_start;
use function session_status;
use function session_write_close;

use const PHP_SESSION_ACTIVE;

final class SessionCsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            return;
        }

        session_id('ray-csrf-test');
        session_start();
        $_SESSION = [];
    }

    public function testIssueVerifyAndClear(): void
    {
        $csrf = new SessionCsrfToken();
        $token = $csrf->issue();

        $this->assertNotSame('', $token);
        $this->assertTrue($csrf->verify($token));
        $this->assertFalse($csrf->verify('wrong-token'));

        $csrf->clear();
        $this->assertFalse($csrf->verify($token));
    }

    public function testIssueReturnsTheSameTokenWithinASession(): void
    {
        $csrf = new SessionCsrfToken();

        $this->assertSame($csrf->issue(), $csrf->issue());
    }

    public function testStartsSessionWhenInactive(): void
    {
        session_write_close();
        $this->assertNotSame(PHP_SESSION_ACTIVE, session_status());

        $token = (new SessionCsrfToken())->issue();

        $this->assertNotSame('', $token);
        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    /**
     * Which slot holds the token is part of the contract: an application sharing a session
     * with an existing system has to name it, and interoperability breaks silently if the
     * key is fixed.
     */
    public function testTokenIsStoredUnderTheConfiguredKey(): void
    {
        $token = (new SessionCsrfToken(new CsrfSessionKey('cms_token')))->issue();

        $this->assertSame($token, $_SESSION['cms_token'] ?? null);
        $this->assertArrayNotHasKey(CsrfSessionKey::DEFAULT_NAME, $_SESSION);
    }

    public function testDefaultKeyIsUsedWhenNoneIsConfigured(): void
    {
        $token = (new SessionCsrfToken())->issue();

        $this->assertSame($token, $_SESSION[CsrfSessionKey::DEFAULT_NAME] ?? null);
    }

    public function testTokensDoNotVerifyAcrossKeys(): void
    {
        $issued = (new SessionCsrfToken(new CsrfSessionKey('area_a')))->issue();

        $other = new SessionCsrfToken(new CsrfSessionKey('area_b'));

        $this->assertFalse($other->verify($issued));
    }

    public function testClearRemovesOnlyTheConfiguredKey(): void
    {
        $_SESSION['unrelated'] = 'keep';
        $csrf = new SessionCsrfToken(new CsrfSessionKey('cms_token'));
        $csrf->issue();

        $csrf->clear();

        $this->assertArrayNotHasKey('cms_token', $_SESSION);
        $this->assertSame('keep', $_SESSION['unrelated']);
    }
}
