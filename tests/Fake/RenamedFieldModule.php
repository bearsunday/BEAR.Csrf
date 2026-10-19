<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Csrf\CsrfModule;
use BEAR\Csrf\CsrfTokenInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/** Same as TestCsrfModule, with the wire field renamed away from the default. */
final class RenamedFieldModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(CsrfModule::withSameOriginCheck('https://example.com', 'csrfToken'));
        $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class)->in(Scope::SINGLETON);
    }
}
