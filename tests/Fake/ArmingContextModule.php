<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use Override;
use Ray\Di\AbstractModule;

/**
 * The shape a BEAR.Sunday context module takes: constructed over the shared
 * module that installed CsrfModule, and installing the arming module from
 * its own configure(). This is the precedence path a production context
 * actually goes through, which differs from override() on a built module.
 */
final class ArmingContextModule extends AbstractModule
{
    public function __construct(private readonly string $origin, AbstractModule $shared)
    {
        parent::__construct($shared);
    }

    #[Override]
    protected function configure(): void
    {
        $this->install(new ArmedOriginModule($this->origin));
    }
}
