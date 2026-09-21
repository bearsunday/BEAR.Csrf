<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsrfSessionKeyTest extends TestCase
{
    public function testDefaultNameMatchesSharedConstant(): void
    {
        $this->assertSame(CsrfSessionKey::DEFAULT_NAME, (new CsrfSessionKey())->name);
    }

    public function testCustomName(): void
    {
        $this->assertSame('cms_token', (new CsrfSessionKey('cms_token'))->name);
    }

    /** An empty key would silently collapse to $_SESSION[''] and share one token. */
    public function testEmptyNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CsrfSessionKey('');
    }
}
