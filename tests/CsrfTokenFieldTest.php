<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Http\CsrfTokenField;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /** PHP stores "csrf.token" and "csrf token" as "csrf_token", and "csrf[token]" as an array. */
    #[DataProvider('namesPhpRewrites')]
    public function testNamePhpWouldRewriteIsRejected(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('would not survive form submission');

        new CsrfTokenField($name);
    }

    /** @return iterable<string, array{string}> */
    public static function namesPhpRewrites(): iterable
    {
        yield 'dot' => ['csrf.token'];
        yield 'space' => ['csrf token'];
        yield 'brackets' => ['csrf[token]'];
    }

    public function testHyphenatedNameIsAccepted(): void
    {
        $this->assertSame('csrf-token', (new CsrfTokenField('csrf-token'))->name);
    }
}
