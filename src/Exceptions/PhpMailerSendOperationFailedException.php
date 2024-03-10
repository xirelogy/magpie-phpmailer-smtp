<?php

namespace MagpieLib\PhpMailerSmtp\Exceptions;

use Magpie\Locales\Concepts\Localizable;

/**
 * Exception due to send operation failed (caused by PHP mailer)
 */
class PhpMailerSendOperationFailedException extends PhpMailerOperationFailedException
{
    /**
     * Constructor
     * @param string|null $mailerErrorInfo
     */
    public function __construct(?string $mailerErrorInfo = null)
    {
        $message = static::formatMessage($mailerErrorInfo);

        parent::__construct($message);
    }


    /**
     * Format error message
     * @param string|null $mailerErrorInfo
     * @return Localizable
     */
    protected static function formatMessage(?string $mailerErrorInfo) : Localizable
    {
        $defaultMessage = 'Send mail operation failed';

        if (!is_empty_string($mailerErrorInfo)) {
            return _format_l($defaultMessage, 'Send mail operation failed: {{0}}', $mailerErrorInfo);
        } else {
            return _l($defaultMessage);
        }
    }
}