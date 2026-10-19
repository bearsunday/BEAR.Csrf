<?php

declare(strict_types=1);

namespace BEAR\Csrf\Interceptor;

use BEAR\Csrf\CsrfTokenInterface;
use BEAR\Csrf\Exception\InvalidCsrfTokenForbiddenException;
use BEAR\Csrf\Exception\MissingCsrfTokenForbiddenException;
use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\RequestTokenInterface;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final readonly class CsrfTokenInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfTokenInterface $csrf,
        private RequestTokenInterface $requestToken,
        private CsrfTokenField $field,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $submitted = $this->requestToken->submitted($invocation, $this->field);

        // A missing token is still submitted to the bound CsrfTokenInterface, as ''. Rejecting
        // it here would make the interceptor, not the port, the final authority — and a
        // consumer wanting a permissive token for tests whose subject is not CSRF would have
        // to replace the interceptor rather than bind an implementation. The two exceptions
        // distinguish the cases for diagnosis; both are 403.
        if (! $this->csrf->verify($submitted ?? '')) {
            throw $submitted === null
                ? new MissingCsrfTokenForbiddenException()
                : new InvalidCsrfTokenForbiddenException();
        }

        return $invocation->proceed();
    }
}
