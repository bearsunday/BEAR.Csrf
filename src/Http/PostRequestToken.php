<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use Override;
use Ray\Aop\MethodInvocation;

use function is_string;

/** @SuppressWarnings("PHPMD.Superglobals") Form body adapter boundary. */
final readonly class PostRequestToken implements RequestTokenInterface
{
    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null
    {
        unset($invocation);

        $value = $_POST[$field->name] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
