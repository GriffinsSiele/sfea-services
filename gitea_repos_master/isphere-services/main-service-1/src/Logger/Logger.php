<?php

declare(strict_types=1);

namespace App\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger
{
    private const LOGGER_NAME = 'main-service';
    private const LOGGER_STREAM_PATH = 'php://stderr';
    private const LOG_LEVEL = MonologLogger::DEBUG;

    /**
     * @var Logger|null
     */
    private static $instance = null;

    /**
     * @var MonologLogger|null
     */
    private $logger = null;

    public static function getInstance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        return self::$instance = new self();
    }

    public static function getLogger(): MonologLogger
    {
        return self::getInstance()->logger;
    }

    public function __construct()
    {
        $this->logger = new MonologLogger(self::LOGGER_NAME);
        $this->logger->pushHandler(new StreamHandler(self::LOGGER_STREAM_PATH, self::LOG_LEVEL));
    }
}
