<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use Ray\Aop\MethodInvocation;

interface RequestTokenInterface
{
    /** @param MethodInvocation<object> $invocation */
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null;
}
