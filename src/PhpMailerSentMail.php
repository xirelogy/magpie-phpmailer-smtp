<?php

namespace MagpieLib\PhpMailerSmtp;

use Magpie\Facades\Smtp\SmtpSentMail;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sent email
 */
abstract class PhpMailerSentMail extends SmtpSentMail
{
    /**
     * @var PHPMailer Mailer instance
     */
    protected readonly PHPMailer $mailer;


    /**
     * Constructor
     * @param PHPMailer $mailer
     */
    protected function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
    }


    /**
     * @inheritDoc
     */
    public function getMessageId() : string
    {
        return $this->mailer->getLastMessageID();
    }


    /**
     * @inheritDoc
     */
    public function exportAsMimeMessage() : string
    {
        return $this->mailer->getSentMIMEMessage();
    }
}