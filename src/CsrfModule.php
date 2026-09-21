<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Attribute\CsrfToken;
use BEAR\Csrf\Attribute\SameOrigin;
use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Csrf\Http\CompositeRequestToken;
use BEAR\Csrf\Http\CsrfTokenField;
use BEAR\Csrf\Http\HeaderRequestToken;
use BEAR\Csrf\Http\PostRequestToken;
use BEAR\Csrf\Http\RequestOriginInterface;
use BEAR\Csrf\Http\RequestTokenInterface;
use BEAR\Csrf\Http\ResourceQueryRequestToken;
use BEAR\Csrf\Http\ServerRequestOrigin;
use BEAR\Csrf\Interceptor\CsrfTokenInterceptor;
use BEAR\Csrf\Interceptor\SameOriginInterceptor;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class CsrfModule extends AbstractModule
{
    /**
     * @param string|null $allowedOrigin The canonical origin, or null to run without the
     *                                   same-origin gate. No default: an origin nobody chose
     *                                   is how a security control ends up off in production.
     *                                   Use the named constructors — `null` written out is
     *                                   greppable in a way an omitted argument is not.
     */
    private function __construct(
        private readonly string|null $allowedOrigin,
        private readonly string $tokenField,
        private readonly string $sessionKey,
    ) {
        parent::__construct();
    }

    /** Both gates on: the token gate always, the same-origin gate against $allowedOrigin. */
    public static function withSameOriginCheck(
        string $allowedOrigin,
        string $tokenField = CsrfTokenField::DEFAULT_NAME,
        string $sessionKey = CsrfSessionKey::DEFAULT_NAME,
    ): self {
        return new self($allowedOrigin, $tokenField, $sessionKey);
    }

    /**
     * Token gate only. For a host with no browser origin to compare against — a CLI or a
     * machine-to-machine API — where the same-origin gate would reject every request rather
     * than protect it. The token gate stays on either way; the two are independent defences.
     */
    public static function withoutSameOriginCheck(
        string $tokenField = CsrfTokenField::DEFAULT_NAME,
        string $sessionKey = CsrfSessionKey::DEFAULT_NAME,
    ): self {
        return new self(null, $tokenField, $sessionKey);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin($this->allowedOrigin));
        $this->bind(CsrfTokenField::class)->toInstance(new CsrfTokenField($this->tokenField));
        $this->bind(CsrfSessionKey::class)->toInstance(new CsrfSessionKey($this->sessionKey));

        $this->bind(RequestOriginInterface::class)->to(ServerRequestOrigin::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(SameOrigin::class),
            [SameOriginInterceptor::class],
        );

        $this->bind(CsrfTokenInterface::class)->to(SessionCsrfToken::class)->in(Scope::SINGLETON);
        $this->bind(HeaderRequestToken::class);
        $this->bind(ResourceQueryRequestToken::class);
        $this->bind(PostRequestToken::class);
        $this->bind(RequestTokenInterface::class)->to(CompositeRequestToken::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(CsrfToken::class),
            [CsrfTokenInterceptor::class],
        );
    }
}
