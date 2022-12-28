<?php

namespace MagpieLib\PhpMailerSmtp;

use Exception;
use Magpie\Exceptions\OperationFailedException;
use Magpie\Exceptions\UnsupportedValueException;
use Magpie\Facades\Smtp\HtmlMailBody;
use Magpie\Facades\Smtp\MailBody;
use Magpie\Facades\Smtp\PlaintextMailBody;
use Magpie\Facades\Smtp\SmtpBasicAuthentication;
use Magpie\Facades\Smtp\SmtpClientConfig;
use Magpie\Facades\Smtp\SmtpMail;
use Magpie\Facades\Smtp\SmtpRecipientType;
use Magpie\Facades\Smtp\SmtpSecurity;
use Magpie\Facades\Smtp\SmtpSentMail;
use Magpie\General\Concepts\BinaryDataProvidable;
use Magpie\General\Contents\BinaryContent;
use Magpie\Logs\Concepts\Loggable;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Envelop of composing email
 */
abstract class PhpMailerMail extends SmtpMail
{
    /**
     * @var PHPMailer Mailer instance
     */
    protected PHPMailer $mailer;
    /**
     * @var Loggable Set logger
     */
    protected Loggable $logger;


    /**
     * Constructor
     * @param SmtpClientConfig $config
     * @param Loggable|null $logger
     * @throws Exception
     */
    protected function __construct(SmtpClientConfig $config, ?Loggable $logger)
    {
        parent::__construct();

        $this->mailer = new PHPMailer(true);
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->isSMTP();
        $this->mailer->Host = $config->host;
        $this->mailer->Port = $config->port ?? SmtpClientConfig::DEFAULT_PORT;
        $this->mailer->SMTPSecure = static::acceptSecurity($config->security);

        if ($config->hasAuth()) {
            if ($config->authentication instanceof SmtpBasicAuthentication) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $config->authentication->username;
                $this->mailer->Password = $config->authentication->password;
            } else {
                throw new UnsupportedValueException($config->authentication, _l('SMTP authentication'));
            }
        }

        if ($logger !== null) {
            $this->logger = $logger;

            $this->mailer->Debugoutput = function(string $message, int $level) {
                switch ($level) {
                    case SMTP::DEBUG_CLIENT:
                    case SMTP::DEBUG_SERVER:
                        // Client/server interactions
                        $this->logger->notice($message);
                        break;
                    case SMTP::DEBUG_CONNECTION:
                        // Connection detail level
                        $this->logger->info($message);
                        break;
                    default:
                        // Including SMTP::DEBUG_LOWLEVEL
                        $this->logger->debug($message);
                        break;
                }
            };
            $this->mailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
        }
    }


    /**
     * @inheritDoc
     */
    public function withSender(string $email, ?string $name = null) : static
    {
        static::ensureSuccessful($this->mailer->setFrom($email, $name ?? ''));
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function withRecipient(string $email, ?string $name = null, SmtpRecipientType $type = SmtpRecipientType::TO) : static
    {
        switch ($type) {
            case SmtpRecipientType::TO:
                static::ensureSuccessful($this->mailer->addAddress($email, $name));
                break;
            case SmtpRecipientType::CC:
                static::ensureSuccessful($this->mailer->addCC($email, $name));
                break;
            case SmtpRecipientType::BCC:
                static::ensureSuccessful($this->mailer->addBCC($email, $name));
                break;
            default:
                throw new UnsupportedValueException($type, _l('recipient type'));
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function withSubject(string $subject) : static
    {
        $this->mailer->Subject = $subject;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function withBody(MailBody $body) : static
    {
        if ($body instanceof HtmlMailBody) {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $body->body;
            $this->mailer->AltBody = $this->mailer->html2text($body->body);
        } else if ($body instanceof PlaintextMailBody) {
            $this->mailer->isHTML(false);
            $this->mailer->Body = $body->body;
        } else {
            throw new UnsupportedValueException($body, _l('mail body'));
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function withAttachment(BinaryDataProvidable $content) : static
    {
        $content = BinaryContent::getFileSystemAccessible($content, $isReleasable);
        $fileSystemPath = $content->getFileSystemPath();

        $result = $this->mailer->addAttachment(
            $fileSystemPath,
            $content->getFilename() ?? '',
            PHPMailer::ENCODING_BASE64,
            $content->getMimeType() ?? '',
        );
        static::ensureSuccessful($result);

        if ($isReleasable) $this->releasedAfterSent->addIfReleasable($content);
        return $this;
    }


    /**
     * @inheritDoc
     */
    protected function onSend() : SmtpSentMail
    {
        if (!$this->mailer->send()) {
            throw new Exception($this->mailer->ErrorInfo);
        }

        return new class($this->mailer) extends PhpMailerSentMail {
            /**
             * Constructor
             * @param PHPMailer $mailer
             */
            public function __construct(PHPMailer $mailer)
            {
                parent::__construct($mailer);
            }
        };
    }


    /**
     * Adapt security configuration to php-mailer convention
     * @param SmtpSecurity $security
     * @return string
     */
    protected static function acceptSecurity(SmtpSecurity $security) : string
    {
        return match ($security) {
            SmtpSecurity::SSL => PHPMailer::ENCRYPTION_SMTPS,
            SmtpSecurity::TLS => PHPMailer::ENCRYPTION_STARTTLS,
            default => '',
        };
    }


    /**
     * Ensure the return result means successful
     * @param bool $result
     * @return void
     * @throws OperationFailedException
     */
    protected static function ensureSuccessful(bool $result) : void
    {
        if (!$result) throw new OperationFailedException();
    }
}