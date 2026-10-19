<?php

declare(strict_types=1);

namespace BEAR\Csrf\Http;

use InvalidArgumentException;

use function preg_match;
use function sprintf;
use function strtolower;

/** The wire name of the token field. Only names PHP stores in $_POST unchanged are accepted. */
final readonly class CsrfTokenField
{
    /** Default wire name shared with {@see \BEAR\Csrf\CsrfModule}. */
    public const DEFAULT_NAME = '_csrf_token';

    public function __construct(
        public string $name = self::DEFAULT_NAME,
    ) {
        if ($this->name === '') {
            throw new InvalidArgumentException('CSRF token field name must not be empty.');
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/D', $this->name) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'CSRF token field name "%s" would not survive form submission: PHP rewrites dots and spaces and parses brackets.',
                $this->name,
            ));
        }

        if (strtolower($this->name) === '_charset_') {
            throw new InvalidArgumentException('CSRF token field name "_charset_" is reserved: browsers replace its value with the form encoding.');
        }
    }
}
