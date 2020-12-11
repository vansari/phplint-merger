<?php
declare(strict_types = 1);

namespace PhpLintMerger\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function testRunXmlMerges(): void {
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
}