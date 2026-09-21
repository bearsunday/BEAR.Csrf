<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Http\ServerRequestOrigin;
use PHPUnit\Framework\TestCase;

final class ServerRequestOriginTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_SEC_FETCH_SITE'], $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);
    }

    public function testReadsHeaders(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
        $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
        $_SERVER['HTTP_REFERER'] = 'https://example.com/page';

        $request = new ServerRequestOrigin();

        $this->assertSame('same-origin', $request->fetchSite());
        $this->assertSame('https://example.com', $request->origin());
        $this->assertSame('https://example.com/page', $request->referer());
    }

    public function testReturnsNullWhenHeadersAbsent(): void
    {
        $request = new ServerRequestOrigin();

        $this->assertNull($request->fetchSite());
        $this->assertNull($request->origin());
        $this->assertNull($request->referer());
    }
}
