<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use BEAR\Resource\ResourceObject;
use Override;
use Ray\Aop\MethodInvocation;

use function is_string;

final readonly class ResourceQueryRequestToken implements RequestTokenInterface
{
    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null
    {
        $resource = $invocation->getThis();
        if (! $resource instanceof ResourceObject) {
            return null;
        }

        $value = $resource->uri->query[$field->name] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
