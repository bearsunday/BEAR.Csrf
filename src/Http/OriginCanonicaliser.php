<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use function array_intersect_key;
use function array_key_exists;
use function parse_url;
use function sprintf;
use function strtolower;

/**
 * The one canonicalisation rule `SameOriginInterceptor` judges every origin by — the
 * configured `AllowedOrigin`, the `Origin` header, and the `Referer` header alike. Extracted
 * so a module can validate a configured origin before it is bound, instead of only at the
 * first request that reaches the interceptor.
 */
final class OriginCanonicaliser
{
    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443];
    private const NON_ORIGIN_PARTS = ['user' => 0, 'pass' => 0, 'query' => 0, 'fragment' => 0];

    public static function canonicaliseOrigin(string $value): string|null
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

        return self::renderCanonical($parts['scheme'], $parts['host'], $parts['port'] ?? null);
    }

    public static function canonicaliseReferer(string $value): string|null
    {
        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return self::renderCanonical($parts['scheme'], $parts['host'], $parts['port'] ?? null);
    }

    private static function renderCanonical(string $scheme, string $host, int|null $port): string
    {
        $scheme = strtolower($scheme);
        $host = strtolower($host);

        if ($port !== null && isset(self::DEFAULT_PORTS[$scheme]) && self::DEFAULT_PORTS[$scheme] === $port) {
            $port = null;
        }

        return $port === null ? sprintf('%s://%s', $scheme, $host) : sprintf('%s://%s:%d', $scheme, $host, $port);
    }
}
