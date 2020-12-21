<?php
declare(strict_types = 1);

namespace PhpLintMerger\Command;

use DOMDocument;
use DOMElement;
use Exception;
use Monolog\Logger;
use PhpLintMerger\Logger\LoggerFactory;
use SimpleXMLElement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class XmlMergeCommand
 * Inspired by Nimut\PhpunitMerger\Command\LogCommand
 */
class XmlMergeCommand extends Command {

    /**
     * @var int
     */
    private $testCount = 0;

    /**
     * @var DOMDocument
     */
    private $xmlDocument;

    /**
     * @var DOMElement[]
     */
    private $domElements = [];

    /**
     * @var array
     */
    private $errorElements = [];

    /**
     * @var int
     */
    private $errorCount = 0;

    private const NODE_TESTSUITE = 'testsuite';
    private const NODE_TESTCASE = 'testcase';
    private const NODE_ERROR = 'error';

    private const ATTR_ERROR = 'errors';
    private const ATTR_NAME = 'name';
    private const ATTR_TESTS = 'tests';
    private const ATTR_TIMESTAMP = 'timestamp';
    private const ATTR_TIME = 'time';

    /** @var null|Logger */
    private $logger = null;

    /**
     * @return LoggerFactory|null
     * @codeCoverageIgnore
     */
    public function getLogger(): ?Logger {
        return $this->logger;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected function configure() {
        $this
            ->setName('xml')
            ->setDescription('Merges multi xml files from overtrue/phplint')
            ->addArgument(
            'in',
                InputArgument::REQUIRED,
                'Directory of the xml reports'
            )
            ->addArgument(
                'out',
                InputArgument::REQUIRED,
                'The final report xml file.'
            )
            ->addOption(
                'level',
                ['--level', '-l'],
                InputArgument::OPTIONAL,
                'The log level [INFO|DEBUG|ERROR]',
                'INFO'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $logLevel = strtoupper($input->getOption('level'));
        if (!in_array($logLevel, ['INFO', 'DEBUG', 'ERROR'])) {
            $output->writeln('Wrong log level given.');
            return 1;
        }
        $this->logger = LoggerFactory::createConsoleLogger(
            pathinfo(__FILE__, PATHINFO_FILENAME),
            $logLevel
        );
        $finder = new Finder();
        $finder->in(realpath($input->getArgument('in')));
        $finder->sortByName();

        $this->xmlDocument = new DOMDocument('1.0', 'UTF-8');
        $this->xmlDocument->formatOutput = true;

        $root = $this->xmlDocument->createElement('testsuites');
        $this->xmlDocument->appendChild($root);

        foreach ($finder as $file) {
            if ($file->isDir()) {
                continue;
            }
            try {
                $xml = new SimpleXMLElement(file_get_contents($file->getPathname()));
                $testSuites = get_object_vars($xml);
                if (empty($testSuites)) {
                    continue;
                }
                // convert all to array
                $testSuites = is_array($tmp = $testSuites[self::NODE_TESTSUITE]) ? $tmp : [$tmp];
                $this->getLogger()->info('Parsing ' . $file->getFilename());
                $this->parseTestSuites($root, $testSuites);
            } catch (Exception $exception) {
                throw $exception;
            }
        }

        // We have only one TestSuite => PHP Linter
        /** @var DOMElement $testSuite */
        $testSuite = $root->getElementsByTagName(self::NODE_TESTSUITE)[0];
        if (null === $testSuite) {
            $this->getLogger()->info('No testsuite was merged');
            return 0;
        }
        // Set the over all Tests count
        $this->getLogger()->info('Overall Tests: ' . (string)$this->testCount);
        $testSuite->setAttribute(self::ATTR_TESTS, (string)$this->testCount);
        // Set the overall Errors
        $overallErrorCount = (string)array_sum(array_map('count', $this->errorElements));
        $this->getLogger()->info('Overall Errors: ' . $overallErrorCount);
        $testSuite->setAttribute(self::ATTR_ERROR, $overallErrorCount);

        // remove time and Timestamp because it is not true anymore
        $this->getLogger()->debug(
            'Removing following attributes: '
            . implode(', ', [self::ATTR_TIME, self::ATTR_TIMESTAMP])
        );
        $testSuite->removeAttribute(self::ATTR_TIME);
        $testSuite->removeAttribute(self::ATTR_TIMESTAMP);

        $file = $input->getArgument('out');
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0777, true);
        }
        $this->xmlDocument->save($input->getArgument('out'));

        return 0;
    }

    /**
     * @param DOMElement $parent
     * @param SimpleXMLElement[] $testSuites
     */
    private function parseTestSuites(DOMElement $parent, array $testSuites): void {
        foreach ($testSuites as $testSuite) {
            $name = (string)$testSuite->attributes()->{self::ATTR_NAME};
            if (isset($this->domElements[$name])) {
                $this->getLogger()->debug($name . ' is set as Element and can be used');
                $element = $this->domElements[$name];
            } else {
                $this->getLogger()->debug('Create new Element for ' . $name);
                $element = $this->xmlDocument->createElement(self::NODE_TESTSUITE);
                foreach ($testSuite->attributes() as $key => $value) {
                    $element->setAttribute($key, (string)$value);
                }
                $parent->appendChild($element);
                $this->domElements[$name] = $element;
            }
            $testCases = get_object_vars($testSuite);
            if (!empty($testCases[self::NODE_TESTCASE] ?? [])) {
                $children = is_array($tmp = $testCases[self::NODE_TESTCASE]) ? $tmp : [$tmp];
                $this->parseTestCaseAndErrors($element, $children);
            }
            // set all over error count
            $element->setAttribute(self::ATTR_ERROR, (string)$this->errorCount);
        }
    }

    /**
     * @param DOMElement $parent
     * @param SimpleXMLElement[] $testCases
     */
    private function parseTestCaseAndErrors(DOMElement $parent, array $testCases): void {
        foreach ($testCases as $testCase) {
            $parsedTestCases = [];
            $errors = (int)$testCase->attributes()->{self::ATTR_ERROR};
            if (0 === $errors) {
                $this->getLogger()->debug('No errors in testcase.');
                continue;
            }
            $this->getLogger()->debug("Found $errors errors in testcase.");
            $name = null;
            /** @var SimpleXMLElement $error */
            foreach($testCase->{self::NODE_ERROR} as $error) {
                $name = (string)$error[0];
                if (isset($this->domElements[$name])) {
                    $this->getLogger()->debug('TestCase-Element found with name ' . $name);
                    $element = $this->domElements[$name];
                } else {
                    $this->getLogger()->debug('Create TestCase-Element with name ' . $name);
                    $element = $this->xmlDocument->createElement(self::NODE_TESTCASE);
                    $element->setAttribute(self::ATTR_NAME, $name);
                    foreach ($testCase->attributes() as $key => $value) {
                        $element->setAttribute($key, (string)$value);
                    }
                    $parent->appendChild($element);
                    $this->domElements[$name] = $element;
                }
                $this->getLogger()->debug('Handle Errors of ' . $name);
                $errorElement = $this->xmlDocument->createElement(self::NODE_ERROR);
                foreach ($error->attributes() as $key => $value) {
                    $castString = trim((string)$value);
                    $errorElement->setAttribute($key, $castString);
                }
                $this->getLogger()->debug('Append error node to testcase.');
                $element->appendChild($errorElement);
                $this->errorElements[$name][] = $element;
                $this->getLogger()->debug(count($this->errorElements[$name]) . ' errors added.');
                $this->getLogger()->debug($name . ': ' . (array_key_exists($name, $this->domElements) ? 'exists' : 'not exists'));
                $parsedTestCases[] = $name;
            }
            if ($name === null) {
                continue;
            }
            $parsedTestCases = array_unique($parsedTestCases);
            foreach ($parsedTestCases as $parsedTestCase) {
                /** @var DOMElement|null $element */
                $element = $this->domElements[$parsedTestCase];

                if (null !== $element) {
                    $this->errorCount += $errorCount = count($this->errorElements[$parsedTestCase]);
                    $element->setAttribute(self::ATTR_ERROR, (string)$errorCount);
                    $this->getLogger()->debug("Found $errorCount Errors for TestCase $parsedTestCase");
                }
            }
            $this->testCount += count($parsedTestCases);
        }
    }
}
