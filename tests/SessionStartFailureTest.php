<?php

declare(strict_types=1);

namespace BEAR\Csrf;

use BEAR\Csrf\Exception\SessionStartFailedException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function ini_set;
use function restore_error_handler;
use function set_error_handler;

final class SessionStartFailureTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUnwritableSavePathIsReportedWhereItHappens(): void
    {
        ini_set('session.save_path', __DIR__ . '/tmp/no-such-dir/sessions');
        set_error_handler(static fn (): bool => true);

        try {
            $this->expectException(SessionStartFailedException::class);

            (new SessionCsrfStore())->get();
        } finally {
            restore_error_handler();
        }
    }
}
