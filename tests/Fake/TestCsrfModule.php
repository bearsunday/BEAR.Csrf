<?php

declare(strict_types=1);

namespace Ray\Csrf\Fake;

use Override;
use Ray\Csrf\CsrfModule;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

final class TestCsrfModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(CsrfModule::withSameOriginCheck('https://example.com'));
        $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class)->in(Scope::SINGLETON);
    }
}
