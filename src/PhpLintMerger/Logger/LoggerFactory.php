<?php
declare(strict_types = 1);

namespace PhpLintMerger\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Logger as MonoLogger;

class LoggerFactory {

    private const LOGLEVEL_MAP = [
        'INFO' => Logger::INFO,
        'DEBUG' => Logger::DEBUG,
        'ERROR' => Logger::ERROR,
    ];

    public static function createConsoleLogger(
        string $name,
        string $logLevel = 'DEBUG'
    ): MonoLogger {
        $logger = new MonoLogger($name);
        $sysLogger = new StreamHandler('php://stdout', self::LOGLEVEL_MAP[$logLevel]);
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");
        $sysLogger->setFormatter($formatter);
        $logger->pushHandler($sysLogger);

        return $logger;
    }
}