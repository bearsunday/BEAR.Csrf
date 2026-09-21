<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use Override;
use Ray\Aop\MethodInvocation;

use function is_string;

/** @SuppressWarnings("PHPMD.Superglobals") Header adapter boundary. */
final readonly class HeaderRequestToken implements RequestTokenInterface
{
    private const HEADER_KEY = 'HTTP_X_CSRF_TOKEN';

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null
    {
        unset($invocation, $field);

        $value = $_SERVER[self::HEADER_KEY] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
