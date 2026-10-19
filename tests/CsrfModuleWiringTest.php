<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Exception\CrossOriginForbiddenException;
use BEAR\Csrf\Exception\MissingCsrfTokenForbiddenException;
use BEAR\Csrf\Fake\RenamedFieldModule;
use BEAR\Csrf\Fake\TestCsrfModule;
use BEAR\Csrf\Fake\TokenGateOnlyModule;
use BEAR\Csrf\Fake\WiredResource;
use BEAR\Csrf\Html\CsrfHiddenInput;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function is_dir;
use function mkdir;
use function preg_match;

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

    /** The field a template renders is the one the interceptor reads: both come from the module. */
    public function testRenamedFieldPassesTheGateUnderTheRenderedName(): void
    {
        $injector = $this->injector(new RenamedFieldModule());
        $html = $injector->getInstance(CsrfHiddenInput::class)();
        $this->assertSame('<input type="hidden" name="csrfToken" value="valid-token">', $html);

        $this->assertSame(1, preg_match('/name="([^"]+)" value="([^"]+)"/', $html, $field));
        $_POST[$field[1]] = $field[2];

        $body = $injector->getInstance(WiredResource::class)->onPost()->body;

        $this->assertIsArray($body);
        $this->assertSame('ok', $body['result']);
    }

    public function testRenamedFieldRejectsTheDefaultName(): void
    {
        $_POST['_csrf_token'] = 'valid-token';

        $this->expectException(MissingCsrfTokenForbiddenException::class);

        $this->resource(new RenamedFieldModule())->onPost();
    }

    private function resource(AbstractModule $module): WiredResource
    {
        return $this->injector($module)->getInstance(WiredResource::class);
    }

    /**
     * Ray.Aop weaves by subclassing, so the injector needs a directory to compile into.
     * Without one the resource comes back unwoven and every gate silently does nothing.
     */
    private function injector(AbstractModule $module): Injector
    {
        $tmpDir = __DIR__ . '/tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir);
        }

        return new Injector($module, $tmpDir);
    }
}
