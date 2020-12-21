<?php

namespace PhpLintMerger\Logger;

use InvalidArgumentException;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class LoggerFactoryTest
 * @coversDefaultClass \PhpLintMerger\Logger\LoggerFactory
 */
class LoggerFactoryTest extends TestCase {

    /**
     * @covers ::createConsoleLogger
     */
    public function testCreateConsoleLogger(): void {
        $this->assertInstanceOf(Logger::class, LoggerFactory::createConsoleLogger('foo'));
    }

    public function testCreateConsoleLoggerWillFailWithWrongLevel(): void {
        $this->expectException(InvalidArgumentException::class);
        LoggerFactory::createConsoleLogger('bar', 'FOO');
    }
}
