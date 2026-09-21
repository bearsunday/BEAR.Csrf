<?php

declare(strict_types=1);

namespace BEAR\Csrf\Interceptor;

use BEAR\Csrf\Exception\CrossOriginForbiddenException;
use BEAR\Csrf\Exception\LogicException;
use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Csrf\Http\RequestOriginInterface;
use BEAR\Resource\Exception\BadRequestException;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function array_intersect_key;
use function array_key_exists;
use function in_array;
use function parse_url;
use function sprintf;
use function strtolower;

final readonly class SameOriginInterceptor implements MethodInterceptor
{
    private const UNSAFE_FETCH_SITES = ['cross-site', 'same-site', 'none'];
    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443];
    private const NON_ORIGIN_PARTS = ['user' => 0, 'pass' => 0, 'query' => 0, 'fragment' => 0];

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

        $allowedCanonical = $this->canonicaliseOrigin($allowed);
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
            $originCanonical = $this->canonicaliseOrigin($origin);
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
            $refererCanonical = $this->canonicaliseRefererOrigin($referer);
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

    private function canonicaliseOrigin(string $value): string|null
    {
        if ($value === 'null') {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (array_intersect_key($parts, self::NON_ORIGIN_PARTS) !== []) {
            return null;
        }

        $path = array_key_exists('path', $parts) ? $parts['path'] : '';
        if ($path !== '' && $path !== '/') {
            return null;
        }

        return $this->renderCanonical($parts['scheme'], $parts['host'], $parts['port'] ?? null);
    }

    private function canonicaliseRefererOrigin(string $value): string|null
    {
        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $this->renderCanonical($parts['scheme'], $parts['host'], $parts['port'] ?? null);
    }

    private function renderCanonical(string $scheme, string $host, int|null $port): string
    {
        $scheme = strtolower($scheme);
        $host = strtolower($host);

        if ($port !== null && isset(self::DEFAULT_PORTS[$scheme]) && self::DEFAULT_PORTS[$scheme] === $port) {
            $port = null;
        }

        return $port === null ? sprintf('%s://%s', $scheme, $host) : sprintf('%s://%s:%d', $scheme, $host, $port);
    }
}
