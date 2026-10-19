<?php

declare(strict_types=1);

namespace BEAR\Csrf\Html;

use BEAR\Csrf\CsrfTokenInterface;
use BEAR\Csrf\Http\CsrfTokenField;

use function htmlspecialchars;
use function sprintf;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * The hidden field a form submits the token in. Both attributes are escaped; emit the result raw.
 */
final readonly class CsrfHiddenInput
{
    public function __construct(
        private CsrfTokenInterface $csrf,
        private CsrfTokenField $field,
    ) {
    }

    /** @return non-empty-string */
    public function __invoke(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $this->escape($this->field->name),
            $this->escape($this->csrf->issue()),
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
