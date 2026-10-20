<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use BEAR\Csrf\Http\AllowedOrigin;
use Override;
use Ray\Di\AbstractModule;

/** What a context module does when it is the one that knows the canonical origin. */
final class ArmedOriginModule extends AbstractModule
{
    public function __construct(private readonly string $origin)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin($this->origin));
    }
}
