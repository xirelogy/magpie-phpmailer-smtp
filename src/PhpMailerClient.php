<?php

namespace MagpieLib\PhpMailerSmtp;

use Magpie\Facades\Smtp\SmtpClient;
use Magpie\Facades\Smtp\SmtpClientConfig;
use Magpie\General\Factories\Annotations\FactoryTypeClass;
use Magpie\General\Factories\ClassFactory;
use Magpie\Logs\Concepts\Loggable;
use Magpie\System\Kernel\BootContext;
use Magpie\System\Kernel\BootRegistrar;

/**
 * SMTP client utilizing php-mailer
 */
#[FactoryTypeClass(PhpMailerClient::TYPECLASS, SmtpClient::class)]
class PhpMailerClient extends SmtpClient
{
    /**
     * Current type class
     */
    public const TYPECLASS = 'php-mailer';

    /**
     * @var SmtpClientConfig Associated configuration
     */
    protected SmtpClientConfig $config;
    /**
     * @var Loggable|null Target logger container, if any
     */
    protected ?Loggable $logger = null;


    /**
     * Constructor
     * @param SmtpClientConfig $config
     */
    protected function __construct(SmtpClientConfig $config)
    {
        $this->config = $config;
    }


    /**
     * @inheritDoc
     */
    public function setLogger(Loggable $logger) : bool
    {
        $this->logger = $logger;
        return true;
    }


    /**
     * @inheritDoc
     */
    public function createMail() : PhpMailerMail
    {
        return new class($this->config, $this->logger) extends PhpMailerMail {
            /**
             * Constructor
             * @param SmtpClientConfig $config
             * @param Loggable|null $logger
             */
            public function __construct(SmtpClientConfig $config, ?Loggable $logger)
            {
                parent::__construct($config, $logger);
            }
        };
    }


    /**
     * @inheritDoc
     */
    public static function getTypeClass() : string
    {
        return static::TYPECLASS;
    }


    /**
     * @inheritDoc
     */
    protected static function specificInitialize(SmtpClientConfig $config) : static
    {
        return new static($config);
    }


    /**
     * @inheritDoc
     */
    public static function systemBootRegister(BootRegistrar $registrar) : bool
    {
        $registrar
            ->provides(SmtpClient::class)
            ;

        return true;
    }


    /**
     * @inheritDoc
     */
    public static function systemBoot(BootContext $context) : void
    {
        ClassFactory::includeDirectory(__DIR__);
    }
}