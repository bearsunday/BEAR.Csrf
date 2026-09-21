<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use InvalidArgumentException;

/**
 * Session slot holding the issued token.
 *
 * Configurable because an application rarely owns the session namespace
 * alone: it may share a session with an existing system that fixes the key,
 * or protect several areas independently within one session. A hardcoded key
 * forces such a consumer to reimplement {@see CsrfTokenInterface} to rename
 * a string.
 */
final readonly class CsrfSessionKey
{
    /** Default session key shared with {@see CsrfModule}. */
    public const DEFAULT_NAME = 'bear_csrf_token';

    public function __construct(
        public string $name = self::DEFAULT_NAME,
    ) {
        if ($this->name === '') {
            throw new InvalidArgumentException('CSRF session key must not be empty.');
        }
    }
}
