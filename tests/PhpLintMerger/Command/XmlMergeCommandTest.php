<?php
declare(strict_types = 1);

namespace PhpLintMerger\Command;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class XmlMergeCommandTest
 * @coversDefaultClass \PhpLintMerger\Command\XmlMergeCommand
 */
class XmlMergeCommandTest extends TestCase {
    use ProphecyTrait;

    /** @var string */
    private $outFile;

    /** @var string */
    private $outfileExpected;

    public function setUp(): void {
        $this->outFile = dirname(__FILE__) . '/lint-result.xml';
        $this->outfileExpected = dirname(__FILE__) . '/lint-result-expected.xml';

        if (file_exists($this->outFile)) {
            unlink($this->outFile);
        }
    }

    public function tearDown(): void {
        $this->outFile = dirname(__FILE__) . '/lint-result.xml';
        $this->outfileExpected = dirname(__FILE__) . '/lint-result-expected.xml';

        if (file_exists($this->outFile)) {
            unlink($this->outFile);
        }
    }

    /**
     * @testdox execute the merger and check the result that the content is as expected
     * @covers ::run
     * @covers ::execute
     * @covers ::parseTestSuites
     * @covers ::parseTestCaseAndErrors
     */
    public function testExecute(): void {
        $this->assertFileDoesNotExist($this->outFile);

        $input = new ArgvInput(
            [
                'xml',
                dirname(__FILE__) . '/testfiles/',
                $this->outFile,
            ]
        );

        $output = $this->prophesize(OutputInterface::class);
        $command = new XmlMergeCommand();
        $command->run($input, $output->reveal());

        $this->assertFileExists($this->outFile);
        $this->assertSame(
            file_get_contents($this->outfileExpected),
            file_get_contents($this->outFile)
        );
    }

    /**
     * @testdox execute the merger and check the result that the content is as expected
     * @covers ::execute
     * @covers ::parseTestSuites
     * @covers ::parseTestCaseAndErrors
     */
    public function testExecuteEmptyTestsuites(): void {
        $this->assertFileDoesNotExist($this->outFile);

        $input = new ArgvInput(
            [
                'xml',
                dirname(__FILE__) . '/testfiles/empty/',
                $this->outFile,
            ]
        );

        $output = $this->prophesize(OutputInterface::class);
        $command = new XmlMergeCommand();
        $command->run($input, $output->reveal());
        $this->assertFileDoesNotExist($this->outFile);
    }

    /**
     * @testdox Fails if the loglevel is not allowed
     * @covers ::execute
     */
    public function testExecuteWillBreakWithWrongLogLevel(): void {
        $this->assertFileDoesNotExist($this->outFile);

        $input = new ArgvInput(
            [
                'xml',
                dirname(__FILE__) . '/testfiles/',
                $this->outFile,
                '--level',
                'FALSE'
            ]
        );

        $output = $this->prophesize(OutputInterface::class);
        $command = new XmlMergeCommand();
        $this->assertSame(1, $command->run($input, $output->reveal()));
        $this->assertFileDoesNotExist($this->outFile);
    }
}
