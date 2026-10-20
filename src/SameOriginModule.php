<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Csrf\Http\OriginCanonicaliser;
use InvalidArgumentException;
use Override;
use Ray\Di\AbstractModule;

use function sprintf;

/**
 * Arms the `#[SameOrigin]` gate from a context module.
 *
 * A BEAR.Sunday application composes contexts over a neutral `AppModule`, so the module
 * that knows the canonical origin is often not the one that installed `CsrfModule`.
 * Installing `CsrfModule` again there would stack a second interceptor onto every
 * `#[CsrfToken]` / `#[SameOrigin]` method; rebinding `AllowedOrigin` is the only
 * correct way to arm the gate from outside the module that installed `CsrfModule`.
 *
 * Install `CsrfModule::withoutSameOriginCheck()` in the shared module, then this module
 * from the context that owns the origin:
 *
 * ```php
 * // ProdModule
 * $this->install(new SameOriginModule($_ENV['APP_ORIGIN']));
 * ```
 */
final class SameOriginModule extends AbstractModule
{
    public function __construct(private readonly string $origin)
    {
        parent::__construct();

        if (OriginCanonicaliser::canonicaliseOrigin($this->origin) === null) {
            throw new InvalidArgumentException(sprintf(
                'Same-origin policy: "%s" is not a valid origin — scheme://host, no path, query, or credentials.',
                $this->origin,
            ));
        }
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin($this->origin));
    }
}
