<?php

namespace MagpieLib\PhpMailerSmtp\Impls;

use Magpie\General\Traits\StaticClass;
use MagpieLib\PhpMailerSmtp\Exceptions\PhpMailerOperationFailedException;
use Throwable;

/**
 * Error handling for PHP mailer related operations
 * @internal
 */
class ErrorHandling
{
    use StaticClass;


    /**
     * Run in protected environment
     * @param callable():T $fn
     * @return T
     * @throws PhpMailerOperationFailedException
     * @template T
     */
    public static function protectedRun(callable $fn) : mixed
    {
        try {
            return $fn();
        } catch (Throwable $ex) {
            throw new PhpMailerOperationFailedException(previous: $ex);
        }
    }
}