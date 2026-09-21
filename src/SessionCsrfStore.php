<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Exception\CoroutineUnsafeStoreException;
use Override;

use function call_user_func;
use function class_exists;
use function is_int;
use function is_string;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Keeps the token in PHP's session.
 *
 * Correct only where a request owns its process — PHP-FPM, mod_php, the
 * built-in server, CLI. On a host serving several requests from one persistent
 * worker `$_SESSION` outlives the request, so every visitor of that worker
 * shares one token: protection removed rather than weakened, and invisible
 * because every request still succeeds.
 *
 * The coroutine case throws rather than pretend. The non-coroutine case is
 * equally unsafe and cannot be detected, so absence of that exception is not
 * evidence of safety — see the README.
 *
 * @SuppressWarnings("PHPMD.Superglobals") Session adapter boundary.
 */
final class SessionCsrfStore implements CsrfStoreInterface
{
    public function __construct(
        private readonly CsrfSessionKey $sessionKey = new CsrfSessionKey(),
    ) {
    }

    /** @return non-empty-string|null */
    #[Override]
    public function get(): string|null
    {
        $this->start();

        $stored = $_SESSION[$this->sessionKey->name] ?? null;
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        return $stored;
    }

    #[Override]
    public function set(string $token): void
    {
        $this->start();
        $_SESSION[$this->sessionKey->name] = $token;
    }

    #[Override]
    public function remove(): void
    {
        $this->start();
        unset($_SESSION[$this->sessionKey->name]);
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
            'SessionCsrfStore runs inside a coroutine, where $_SESSION is shared by every '
            . 'concurrent request in the worker and the token stops identifying anyone. '
            . 'Bind CsrfStoreInterface to a request-scoped store.',
        );
    }
}
