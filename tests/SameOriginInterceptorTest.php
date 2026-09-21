<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Exception\CrossOriginForbiddenException;
use BEAR\Csrf\Exception\LogicException;
use BEAR\Csrf\Fake\FakeInvocation;
use BEAR\Csrf\Fake\FakeRequestOrigin;
use BEAR\Csrf\Fake\FakeResource;
use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Csrf\Interceptor\SameOriginInterceptor;
use BEAR\Resource\Exception\BadRequestException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SameOriginInterceptorTest extends TestCase
{
    public function testSameOriginFetchSiteProceeds(): void
    {
        $this->assertSame('proceeded', $this->invoke(new FakeRequestOrigin(fetchSite: 'same-origin')));
    }

    public function testAllowedOriginNullShortCircuits(): void
    {
        $this->assertSame('proceeded', $this->invoke(new FakeRequestOrigin(fetchSite: 'cross-site'), null));
    }

    public function testOriginHeaderMatchingProceeds(): void
    {
        $this->assertSame('proceeded', $this->invoke(new FakeRequestOrigin(origin: 'https://example.com')));
    }

    public function testOriginDefaultPortIsNormalisedAndProceeds(): void
    {
        $this->assertSame('proceeded', $this->invoke(new FakeRequestOrigin(origin: 'https://example.com:443')));
    }

    /** Scheme and host compare case-insensitively per RFC 3986. */
    public function testOriginCaseIsIgnoredWhenComparing(): void
    {
        $this->assertSame('proceeded', $this->invoke(new FakeRequestOrigin(origin: 'HTTPS://EXAMPLE.COM')));
    }

    public function testRefererSameOriginProceeds(): void
    {
        $this->assertSame('proceeded', $this->invoke(new FakeRequestOrigin(referer: 'https://example.com/admin/edit?id=1')));
    }

    #[DataProvider('unsafeFetchSites')]
    public function testUnsafeFetchSiteForbidden(string $fetchSite): void
    {
        $this->expectException(CrossOriginForbiddenException::class);
        $this->expectExceptionMessage('Sec-Fetch-Site: ' . $fetchSite);

        $this->invoke(new FakeRequestOrigin(fetchSite: $fetchSite));
    }

    public function testUnknownFetchSiteForbidden(): void
    {
        $this->expectException(CrossOriginForbiddenException::class);
        $this->expectExceptionMessage('unknown Sec-Fetch-Site value');

        $this->invoke(new FakeRequestOrigin(fetchSite: 'made-up'));
    }

    public function testCrossOriginOriginForbidden(): void
    {
        $this->expectException(CrossOriginForbiddenException::class);
        $this->expectExceptionMessage('cross-origin Origin');

        $this->invoke(new FakeRequestOrigin(origin: 'https://evil.example'));
    }

    public function testCrossOriginNonDefaultPortForbidden(): void
    {
        $this->expectException(CrossOriginForbiddenException::class);
        $this->expectExceptionMessage('cross-origin Origin');

        $this->invoke(new FakeRequestOrigin(origin: 'https://example.com:8443'));
    }

    public function testCrossOriginRefererForbidden(): void
    {
        $this->expectException(CrossOriginForbiddenException::class);
        $this->expectExceptionMessage('cross-origin Referer');

        $this->invoke(new FakeRequestOrigin(referer: 'https://evil.example/page'));
    }

    public function testHeaderAbsentForbidden(): void
    {
        $this->expectException(CrossOriginForbiddenException::class);
        $this->expectExceptionMessage('no Sec-Fetch-Site / Origin / Referer');

        $this->invoke(new FakeRequestOrigin());
    }

    public function testMalformedAllowedOriginIsConfigurationError(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('configured allowed origin is malformed');

        $this->invoke(new FakeRequestOrigin(fetchSite: 'same-origin'), 'https://example.com/path');
    }

    #[DataProvider('malformedOrigins')]
    public function testMalformedOriginBadRequest(string $origin): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('malformed Origin header');

        $this->invoke(new FakeRequestOrigin(origin: $origin));
    }

    public function testMalformedRefererBadRequest(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('malformed Referer header');

        $this->invoke(new FakeRequestOrigin(referer: 'http://'));
    }

    /** @return array<string, array{string}> */
    public static function unsafeFetchSites(): array
    {
        return [
            'cross-site' => ['cross-site'],
            'same-site' => ['same-site'],
            'none' => ['none'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function malformedOrigins(): array
    {
        return [
            'with path' => ['https://evil.example/path'],
            'literal null' => ['null'],
            'missing scheme' => ['example.com'],
            'with userinfo' => ['https://user:pass@example.com'],
        ];
    }

    private function invoke(FakeRequestOrigin $request, string|null $allowedOrigin = 'https://example.com'): mixed
    {
        return (new SameOriginInterceptor(
            $request,
            new AllowedOrigin($allowedOrigin),
        ))->invoke(new FakeInvocation(new FakeResource(), 'onDelete'));
    }
}
