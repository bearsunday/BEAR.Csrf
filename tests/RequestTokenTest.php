<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Fake\FakeInvocation;
use BEAR\Csrf\Fake\FakeResource;
use BEAR\Csrf\Fake\FakeUri;
use BEAR\Csrf\Http\CompositeRequestToken;
use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\HeaderRequestToken;
use BEAR\Csrf\Http\PostRequestToken;
use BEAR\Csrf\Http\ResourceQueryRequestToken;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RequestTokenTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = null;
        $_POST = [];
    }

    public function testHeaderRequestToken(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'header-token';

        $actual = (new HeaderRequestToken())->submitted($this->invocation(), new CsrfTokenField());

        $this->assertSame('header-token', $actual);
    }

    public function testResourceQueryRequestToken(): void
    {
        $resource = new FakeResource();
        $resource->uri = new FakeUri();
        $resource->uri->query = ['_csrf_token' => 'query-token'];

        $actual = (new ResourceQueryRequestToken())->submitted(new FakeInvocation($resource, 'onPost'), new CsrfTokenField());

        $this->assertSame('query-token', $actual);
    }

    public function testResourceQueryRequestTokenIgnoresNonResourceObject(): void
    {
        $actual = (new ResourceQueryRequestToken())->submitted(new FakeInvocation(new stdClass(), 'onPost'), new CsrfTokenField());

        $this->assertNull($actual);
    }

    public function testPostRequestToken(): void
    {
        $_POST['_csrf_token'] = 'post-token';

        $actual = (new PostRequestToken())->submitted($this->invocation(), new CsrfTokenField());

        $this->assertSame('post-token', $actual);
    }

    public function testCompositeRequestTokenPrecedence(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'header-token';
        $_POST['_csrf_token'] = 'post-token';
        $resource = new FakeResource();
        $resource->uri = new FakeUri();
        $resource->uri->query = ['_csrf_token' => 'query-token'];

        $actual = $this->composite()->submitted(new FakeInvocation($resource, 'onPost'), new CsrfTokenField());

        $this->assertSame('header-token', $actual);
    }

    public function testCompositeRequestTokenFallsBackToResourceQueryThenPost(): void
    {
        $_POST['_csrf_token'] = 'post-token';
        $resource = new FakeResource();
        $resource->uri = new FakeUri();
        $resource->uri->query = ['_csrf_token' => 'query-token'];

        $actual = $this->composite()->submitted(new FakeInvocation($resource, 'onPost'), new CsrfTokenField());

        $this->assertSame('query-token', $actual);
    }

    public function testCompositeRequestTokenFallsBackToPost(): void
    {
        $_POST['_csrf_token'] = 'post-token';
        $resource = new FakeResource();
        $resource->uri = new FakeUri();
        $resource->uri->query = [];

        $actual = $this->composite()->submitted(new FakeInvocation($resource, 'onPost'), new CsrfTokenField());

        $this->assertSame('post-token', $actual);
    }

    private function invocation(): FakeInvocation
    {
        return new FakeInvocation(new FakeResource(), 'onPost');
    }

    private function composite(): CompositeRequestToken
    {
        return new CompositeRequestToken(new HeaderRequestToken(), new ResourceQueryRequestToken(), new PostRequestToken());
    }
}
