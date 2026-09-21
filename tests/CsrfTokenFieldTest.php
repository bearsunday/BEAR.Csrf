<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Http\CsrfTokenField;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CsrfTokenFieldTest extends TestCase
{
    public function testDefaultNameMatchesSharedConstant(): void
    {
        $this->assertSame(CsrfTokenField::DEFAULT_NAME, (new CsrfTokenField())->name);
        $this->assertSame('_csrf_token', CsrfTokenField::DEFAULT_NAME);
    }

    public function testCustomName(): void
    {
        $this->assertSame('csrfToken', (new CsrfTokenField('csrfToken'))->name);
    }

    public function testEmptyNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new CsrfTokenField('');
    }
}
