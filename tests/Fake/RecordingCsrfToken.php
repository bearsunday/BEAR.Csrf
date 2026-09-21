<?php

declare(strict_types=1);

namespace BEAR\Csrf\Fake;

use Override;
use BEAR\Csrf\CsrfTokenInterface;

/** Records what the interceptor submitted, and accepts or rejects on command. */
final class RecordingCsrfToken implements CsrfTokenInterface
{
    /** @var list<string> */
    public array $verified = [];

    public function __construct(private readonly bool $accepts = false)
    {
    }

    #[Override]
    public function issue(): string
    {
        return 'recording-token';
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        $this->verified[] = $candidate;

        return $this->accepts;
    }

    #[Override]
    public function clear(): void
    {
    }
}
