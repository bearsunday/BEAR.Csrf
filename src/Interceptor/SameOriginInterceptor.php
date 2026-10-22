<?php

declare(strict_types=1);

namespace BEAR\Csrf\Interceptor;

use BEAR\Csrf\Exception\CrossOriginForbiddenException;
use BEAR\Csrf\Exception\LogicException;
use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Csrf\Http\OriginCanonicaliser;
use BEAR\Csrf\Http\RequestOriginInterface;
use BEAR\Resource\Exception\BadRequestException;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function in_array;
use function sprintf;

final readonly class SameOriginInterceptor implements MethodInterceptor
{
    private const UNSAFE_FETCH_SITES = ['cross-site', 'same-site', 'none'];

    public function __construct(
        private RequestOriginInterface $request,
        private AllowedOrigin $allowedOrigin,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $allowed = $this->allowedOrigin->value;
        if ($allowed === null) {
            return $invocation->proceed();
        }

        $allowedCanonical = OriginCanonicaliser::canonicaliseOrigin($allowed);
        if ($allowedCanonical === null) {
            throw new LogicException('Same-origin policy: configured allowed origin is malformed.');
        }

        $fetchSite = $this->request->fetchSite();
        if ($fetchSite !== null) {
            return $this->checkFetchSite($invocation, $fetchSite);
        }

        return $this->checkOriginOrReferer($invocation, $allowedCanonical);
    }

    /** @param MethodInvocation<object> $invocation */
    private function checkFetchSite(MethodInvocation $invocation, string $fetchSite): mixed
    {
        if ($fetchSite === 'same-origin') {
            return $invocation->proceed();
        }

        if (in_array($fetchSite, self::UNSAFE_FETCH_SITES, true)) {
            throw new CrossOriginForbiddenException(sprintf('Same-origin policy: Sec-Fetch-Site: %s.', $fetchSite));
        }

        throw new CrossOriginForbiddenException(sprintf('Same-origin policy: unknown Sec-Fetch-Site value: %s.', $fetchSite));
    }

    /** @param MethodInvocation<object> $invocation */
    private function checkOriginOrReferer(MethodInvocation $invocation, string $allowedCanonical): mixed
    {
        $origin = $this->request->origin();
        if ($origin !== null) {
            $originCanonical = OriginCanonicaliser::canonicaliseOrigin($origin);
            if ($originCanonical === null) {
                throw new BadRequestException(sprintf('Same-origin policy: malformed Origin header: %s.', $origin));
            }

            if ($originCanonical === $allowedCanonical) {
                return $invocation->proceed();
            }

            throw new CrossOriginForbiddenException(sprintf('Same-origin policy: cross-origin Origin: %s.', $origin));
        }

        $referer = $this->request->referer();
        if ($referer !== null) {
            $refererCanonical = OriginCanonicaliser::canonicaliseReferer($referer);
            if ($refererCanonical === null) {
                throw new BadRequestException(sprintf('Same-origin policy: malformed Referer header: %s.', $referer));
            }

            if ($refererCanonical === $allowedCanonical) {
                return $invocation->proceed();
            }

            throw new CrossOriginForbiddenException(sprintf('Same-origin policy: cross-origin Referer: %s.', $referer));
        }

        throw new CrossOriginForbiddenException('Same-origin policy: no Sec-Fetch-Site / Origin / Referer header.');
    }
}
