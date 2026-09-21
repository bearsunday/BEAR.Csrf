<?php

declare(strict_types=1);

namespace BEAR\Csrf\Exception;

use LogicException;

/**
 * Thrown when a session-backed token store runs inside a coroutine.
 *
 * PHP's `$_SESSION` belongs to the process, so on a host that serves several
 * requests from one persistent worker every visitor shares a single token.
 * That does not weaken CSRF protection, it removes it: an attacker's forged
 * request carries a token the server accepts, and nothing is visible from the
 * outside because every request still succeeds.
 *
 * This catches the coroutine shape of the mistake only. A worker without
 * coroutines reports no coroutine id and the extension exposes no way to ask
 * whether a server is running, so the non-coroutine case is equally unsafe and
 * undetectable. Absence of this exception is not evidence that the store is
 * safe where it is running.
 *
 * Bind {@see \BEAR\Csrf\CsrfTokenInterface} to a store scoped to the request
 * (coroutine context, or a shared backend keyed by the session id).
 */
final class CoroutineUnsafeStoreException extends LogicException
{
}
