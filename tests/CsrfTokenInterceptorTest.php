<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Exception\InvalidCsrfTokenForbiddenException;
use BEAR\Csrf\Exception\MissingCsrfTokenForbiddenException;
use BEAR\Csrf\Fake\FakeCsrfToken;
use BEAR\Csrf\Fake\FakeInvocation;
use BEAR\Csrf\Fake\FakeResource;
use BEAR\Csrf\Fake\FakeUri;
use BEAR\Csrf\Fake\RecordingCsrfToken;
use BEAR\Csrf\Http\CompositeRequestToken;
use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\HeaderRequestToken;
use BEAR\Csrf\Http\PostRequestToken;
use BEAR\Csrf\Http\ResourceQueryRequestToken;
use BEAR\Csrf\Interceptor\CsrfTokenInterceptor;
use PHPUnit\Framework\TestCase;

final class CsrfTokenInterceptorTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = null;
        $_POST = [];
    }

    public function testValidTokenProceeds(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'valid-token';
        $invocation = new FakeInvocation(new FakeResource(), 'onPost');

        $actual = $this->interceptor()->invoke($invocation);

        $this->assertSame('proceeded', $actual);
        $this->assertTrue($invocation->proceeded);
    }

    public function testMissingTokenForbidden(): void
    {
        $this->expectException(MissingCsrfTokenForbiddenException::class);

        $this->interceptor()->invoke(new FakeInvocation(new FakeResource(), 'onPost'));
    }

    public function testInvalidTokenForbidden(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid-token';
        $this->expectException(InvalidCsrfTokenForbiddenException::class);

        $this->interceptor()->invoke(new FakeInvocation(new FakeResource(), 'onPost'));
    }

    public function testBoundFieldIsTheOneRead(): void
    {
        $resource = new FakeResource();
        $resource->uri = new FakeUri();
        $resource->uri->query = ['csrfToken' => 'valid-token'];
        $invocation = new FakeInvocation($resource, 'onPost');

        $actual = $this->interceptor(field: new CsrfTokenField('csrfToken'))->invoke($invocation);

        $this->assertSame('proceeded', $actual);
    }

    /**
     * The bound implementation, not the interceptor, decides whether a request is acceptable.
     * A consumer whose tests are not about CSRF binds a permissive token; if the interceptor
     * rejected the missing case itself, that binding would have no effect and the consumer
     * would have to replace the interceptor.
     */
    public function testMissingTokenIsDecidedByTheBoundImplementation(): void
    {
        $invocation = new FakeInvocation(new FakeResource(), 'onPost');
        $permissive = new RecordingCsrfToken(accepts: true);

        $actual = $this->interceptor($permissive)->invoke($invocation);

        $this->assertSame('proceeded', $actual);
        $this->assertSame([''], $permissive->verified);
    }

    /** A missing token reaches verify() as '' rather than bypassing it. */
    public function testMissingTokenIsSubmittedAsEmptyString(): void
    {
        $recording = new RecordingCsrfToken();

        try {
            $this->interceptor($recording)->invoke(new FakeInvocation(new FakeResource(), 'onPost'));
            $this->fail('Expected the request to be forbidden.');
        } catch (MissingCsrfTokenForbiddenException) {
            $this->assertSame([''], $recording->verified);
        }
    }

    private function interceptor(CsrfTokenInterface|null $csrf = null, CsrfTokenField|null $field = null): CsrfTokenInterceptor
    {
        return new CsrfTokenInterceptor(
            $csrf ?? new FakeCsrfToken('valid-token'),
            new CompositeRequestToken(new HeaderRequestToken(), new ResourceQueryRequestToken(), new PostRequestToken()),
            $field ?? new CsrfTokenField(),
        );
    }
}
