<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use ArrayObject;
use Override;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectionMethod;

/** @implements MethodInvocation<object> */
final class FakeInvocation implements MethodInvocation
{
    public bool $proceeded = false;

    public function __construct(
        private object $target,
        private string $method,
    ) {
    }

    #[Override]
    public function getMethod(): ReflectionMethod
    {
        return new ReflectionMethod($this->target, $this->method);
    }

    /** @return ArrayObject<int, mixed> */
    #[Override]
    public function getArguments(): ArrayObject
    {
        return new ArrayObject([]);
    }

    /** @return ArrayObject<non-empty-string, mixed> */
    #[Override]
    public function getNamedArguments(): ArrayObject
    {
        return new ArrayObject([]);
    }

    #[Override]
    public function proceed(): mixed
    {
        $this->proceeded = true;

        return 'proceeded';
    }

    #[Override]
    public function getThis(): object
    {
        return $this->target;
    }
}
