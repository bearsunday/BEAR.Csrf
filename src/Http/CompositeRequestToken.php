<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use Override;
use Ray\Aop\MethodInvocation;

final readonly class CompositeRequestToken implements RequestTokenInterface
{
    public function __construct(
        private HeaderRequestToken $header,
        private ResourceQueryRequestToken $resourceQuery,
        private PostRequestToken $post,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function submitted(MethodInvocation $invocation, CsrfTokenField $field): string|null
    {
        foreach ([$this->header, $this->resourceQuery, $this->post] as $source) {
            $token = $source->submitted($invocation, $field);
            if ($token !== null) {
                return $token;
            }
        }

        return null;
    }
}
