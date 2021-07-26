<?php

declare(strict_types=1);

namespace PhpLintMerger\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Logger as MonoLogger;

class LoggerFactory
{

    public const LOGLEVEL_MAP = [
        'INFO' => Logger::INFO,
        'DEBUG' => Logger::DEBUG,
        'ERROR' => Logger::ERROR,
    ];

    /**
     * Creates a new MonoLogger which logs to Console
     * @param string $name
     * @param string $logLevel
     * @return MonoLogger
     */
    public static function createConsoleLogger(
        string $name,
        string $logLevel = 'DEBUG'
    ): MonoLogger {
        if (!array_key_exists($logLevel, self::LOGLEVEL_MAP)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The LogLevel is not supported (%s)',
                    implode(', ', self::LOGLEVEL_MAP)
                )
            );
        }
        $logger = new MonoLogger($name);
        $sysLogger = new StreamHandler('php://stdout', self::LOGLEVEL_MAP[$logLevel]);
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
        $sysLogger->setFormatter($formatter);
        $logger->pushHandler($sysLogger);

        return $logger;
    }
}
