<?php

declare(strict_types=1);

namespace Ray\Csrf\Exception;

use LogicException;

/**
 * Thrown when a session-backed token store runs inside a coroutine.
 *
 * PHP's `$_SESSION` belongs to the process, so under a host that serves
 * several requests concurrently in one worker every visitor shares a single
 * token. That does not weaken CSRF protection, it removes it: an attacker's
 * forged request carries a token the server accepts. The condition is
 * invisible from the outside — requests succeed — so the store refuses to run
 * rather than let a deployment believe it is protected.
 *
 * Bind {@see \Ray\Csrf\CsrfTokenInterface} to a store scoped to the request
 * (coroutine context, or a shared backend keyed by the session id).
 */
final class CoroutineUnsafeStoreException extends LogicException
{
}
