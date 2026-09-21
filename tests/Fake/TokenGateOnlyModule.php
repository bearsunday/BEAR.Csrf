<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Csrf\CsrfModule;
use BEAR\Csrf\CsrfTokenInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/** A host with no browser origin to compare against: token gate on, origin gate off. */
final class TokenGateOnlyModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(CsrfModule::withoutSameOriginCheck());
        $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class)->in(Scope::SINGLETON);
    }
}
