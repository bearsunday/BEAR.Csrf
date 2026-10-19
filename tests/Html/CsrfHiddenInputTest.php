<?php

declare(strict_types=1);

namespace BEAR\Csrf\Html;

use BEAR\Csrf\Fake\FakeCsrfToken;
use BEAR\Csrf\Fake\RecordingCsrfToken;
use BEAR\Csrf\Http\CsrfTokenField;
use PHPUnit\Framework\TestCase;

final class CsrfHiddenInputTest extends TestCase
{
    public function testRendersTheBoundFieldNameAndTheIssuedToken(): void
    {
        $input = new CsrfHiddenInput(new FakeCsrfToken('abc123'), new CsrfTokenField('csrfToken'));

        $this->assertSame('<input type="hidden" name="csrfToken" value="abc123">', $input());
    }

    public function testDefaultFieldNameIsTheOneTheInterceptorReads(): void
    {
        $input = new CsrfHiddenInput(new FakeCsrfToken('abc123'), new CsrfTokenField());

        $this->assertSame('<input type="hidden" name="_csrf_token" value="abc123">', $input());
    }

    /** Neither attribute is trusted: the token is opaque and the field name is configuration. */
    public function testBothAttributesAreEscaped(): void
    {
        $input = new CsrfHiddenInput(new FakeCsrfToken('"><script>\'&'), new CsrfTokenField('a"b<c'));

        $this->assertSame(
            '<input type="hidden" name="a&quot;b&lt;c" value="&quot;&gt;&lt;script&gt;&apos;&amp;">',
            $input(),
        );
    }

    public function testInvalidUtf8IsSubstitutedRatherThanDropped(): void
    {
        $input = new CsrfHiddenInput(new FakeCsrfToken("a\xC3b"), new CsrfTokenField());

        $this->assertSame("<input type=\"hidden\" name=\"_csrf_token\" value=\"a\u{FFFD}b\">", $input());
    }

    public function testTokenIsIssuedOnlyWhenRendered(): void
    {
        $csrf = new RecordingCsrfToken();
        $input = new CsrfHiddenInput($csrf, new CsrfTokenField());

        $this->assertSame(0, $csrf->issued);

        $input();

        $this->assertSame(1, $csrf->issued);
    }
}
