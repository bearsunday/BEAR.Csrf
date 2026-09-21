<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Exception\CrossOriginForbiddenException;
use BEAR\Csrf\Exception\MissingCsrfTokenForbiddenException;
use BEAR\Csrf\Fake\TestCsrfModule;
use BEAR\Csrf\Fake\TokenGateOnlyModule;
use BEAR\Csrf\Fake\WiredResource;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function bin2hex;
use function mkdir;
use function random_bytes;
use function sys_get_temp_dir;

/**
 * Proves the attributes become interception through a real injector.
 *
 * Every case here is a rejection. A request carrying a valid token passes whether or not the
 * interceptor was ever woven, so asserting success proves nothing — these tests only pass when
 * the gate actually runs.
 */
final class CsrfModuleWiringTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = null;
        $_SERVER['HTTP_SEC_FETCH_SITE'] = null;
        $_POST = [];
    }

    public function testCsrfTokenInterceptorIsWired(): void
    {
        $resource = $this->resource(new TestCsrfModule());

        $this->expectException(MissingCsrfTokenForbiddenException::class);

        $resource->onPost();
    }

    public function testCsrfTokenInterceptorAcceptsAValidToken(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid-token';

        $body = $this->resource(new TestCsrfModule())->onPost()->body;

        $this->assertIsArray($body);
        $this->assertSame('ok', $body['result']);
    }

    public function testSameOriginInterceptorIsWired(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $this->expectException(CrossOriginForbiddenException::class);

        $this->resource(new TestCsrfModule())->onDelete();
    }

    public function testSameOriginInterceptorAcceptsASameOriginRequest(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';

        $body = $this->resource(new TestCsrfModule())->onDelete()->body;

        $this->assertIsArray($body);
        $this->assertSame('ok', $body['result']);
    }

    /**
     * withoutSameOriginCheck() leaves the origin gate off — a cross-site request passes it —
     * while the token gate still runs. The two are independent defences, and a host with no
     * browser origin to compare against needs exactly this combination.
     */
    public function testWithoutSameOriginCheckDisarmsOnlyTheOriginGate(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        $body = $this->resource(new TokenGateOnlyModule())->onDelete()->body;

        $this->assertIsArray($body);
        $this->assertSame('ok', $body['result']);
    }

    public function testTokenGateStillRunsWithoutTheOriginGate(): void
    {
        $this->expectException(MissingCsrfTokenForbiddenException::class);

        $this->resource(new TokenGateOnlyModule())->onPost();
    }

    /**
     * Ray.Aop weaves by subclassing, so the injector needs a directory to compile into.
     * Without one the resource comes back unwoven and every gate silently does nothing.
     */
    private function resource(AbstractModule $module): WiredResource
    {
        $tmpDir = sys_get_temp_dir() . '/bear-csrf-aop-' . bin2hex(random_bytes(6));
        mkdir($tmpDir);

        return (new Injector($module, $tmpDir))->getInstance(WiredResource::class);
    }
}
