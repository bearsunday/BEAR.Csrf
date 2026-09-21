<?php

declare(strict_types=1);

namespace Ray\Csrf;

use Override;
use Ray\Csrf\Exception\CoroutineUnsafeStoreException;

use function bin2hex;
use function call_user_func;
use function class_exists;
use function hash_equals;
use function is_int;
use function is_string;
use function random_bytes;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Token held in PHP's session.
 *
 * Assumes a host where a request owns its process (CLI server, PHP-FPM).
 * Under a coroutine host the process outlives the request and `$_SESSION`
 * would be shared between concurrent visitors, so the store refuses to run
 * there instead of silently issuing one token to everybody.
 *
 * @SuppressWarnings("PHPMD.Superglobals") Session adapter boundary.
 */
final class SessionCsrfToken implements CsrfTokenInterface
{
    public function __construct(
        private readonly CsrfSessionKey $sessionKey = new CsrfSessionKey(),
    ) {
    }

    #[Override]
    public function issue(): string
    {
        $this->start();

        $existing = $this->storedToken();
        if ($existing !== null) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[$this->sessionKey->name] = $token;

        return $token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        $this->start();

        $stored = $this->storedToken();
        if ($stored === null || $candidate === '') {
            return false;
        }

        return hash_equals($stored, $candidate);
    }

    #[Override]
    public function clear(): void
    {
        $this->start();
        unset($_SESSION[$this->sessionKey->name]);
    }

    /** @return non-empty-string|null */
    private function storedToken(): string|null
    {
        if (! isset($_SESSION[$this->sessionKey->name]) || ! is_string($_SESSION[$this->sessionKey->name]) || $_SESSION[$this->sessionKey->name] === '') {
            return null;
        }

        return $_SESSION[$this->sessionKey->name];
    }

    private function start(): void
    {
        $this->guardAgainstSharedSession();

        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start();
    }

    /** @throws CoroutineUnsafeStoreException When concurrent requests would share one $_SESSION. */
    private function guardAgainstSharedSession(): void
    {
        // Named by string: the extension is not a dependency, and the CI matrix that runs
        // static analysis does not load it.
        if (! class_exists('Swoole\\Coroutine', false)) {
            return;
        }

        $coroutineId = call_user_func('Swoole\\Coroutine::getCid');
        if (! is_int($coroutineId) || $coroutineId < 0) {
            return;
        }

        throw new CoroutineUnsafeStoreException(
            'SessionCsrfToken runs inside a coroutine, where $_SESSION is shared by every '
            . 'concurrent request in the worker and the token stops identifying anyone. '
            . 'Bind CsrfTokenInterface to a request-scoped store.',
        );
    }
}
