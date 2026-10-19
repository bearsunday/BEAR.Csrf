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
 * The hidden field a form submits the token in, as one escaped string.
 *
 * Template helpers delegate to this instead of spelling the input out. The
 * field name comes from the same {@see CsrfTokenField} the interceptor reads,
 * so renaming the wire field cannot leave a form behind, and the token is
 * issued only when a form is actually rendered, so a page without one never
 * opens the session for it. Both attributes are escaped here; the template
 * engine must emit the result raw.
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
