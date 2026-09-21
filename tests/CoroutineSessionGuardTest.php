<?php

declare(strict_types=1);

namespace Ray\Csrf;

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Ray\Csrf\Exception\CoroutineUnsafeStoreException;
use Swoole\Coroutine;
use Throwable;

use function session_id;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Under a coroutine host `$_SESSION` belongs to the worker, not the request, so every
 * concurrent visitor would share one token and forged requests would be accepted. The failure
 * is invisible from outside — requests succeed — so the store has to refuse rather than let a
 * deployment believe it is protected.
 */
#[RequiresPhpExtension('swoole')]
final class CoroutineSessionGuardTest extends TestCase
{
    public function testStoreRefusesToRunInsideACoroutine(): void
    {
        $caught = null;
        Coroutine\run(static function () use (&$caught): void {
            try {
                (new SessionCsrfToken())->issue();
            } catch (Throwable $e) {
                $caught = $e;
            }
        });

        $this->assertInstanceOf(CoroutineUnsafeStoreException::class, $caught);
        $this->assertStringContainsString('request-scoped store', $caught->getMessage());
    }

    public function testStoreWorksOutsideACoroutine(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_id('ray-csrf-coroutine-test');
            session_start();
        }

        $this->assertNotSame('', (new SessionCsrfToken())->issue());
    }
}
