<?php

namespace bin\Cmp\FeatureBalancer\Commands;

use PHP_CodeCoverage;
use PHP_CodeCoverage_Filter;
use PHP_CodeCoverage_Report_Clover;
use PhpSpec\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CodeCoverage extends Command
{
    /**
     * @var string
     */
    private $baseDir;

    /**
     * @var int
     */
    private $exitCode = 0;

    protected function configure()
    {
        $this->baseDir = realpath(__DIR__.'/..').'/';

        $this->setName('code-coverage:run')
            ->setDescription('Runs the code coverage')
            ->addArgument("format", InputArgument::REQUIRED, "The output for the code report: clover or html");
    }

    /**
     * Builds the code coverage clove report file
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!in_array($input->getArgument('format'), ['clover', 'html'])) {
            throw new InvalidArgumentException("The only valid formats are clover or html");
        }

        $coverage = new PHP_CodeCoverage(null, $this->getFilter());
        $coverage->start('<tests>');

        $this->runPhpSpec();

        $coverage->stop();

        if ($input->getArgument('format') == 'clover') {
            $this->writeCloverReport($coverage);
        } elseif ($input->getArgument('format') == 'html') {
            $this->writeHtmlReport($coverage);
        }

        return $this->exitCode;
    }

    /**
     * Writes the clover report
     *
     * @param PHP_CodeCoverage $coverage
     */
    private function writeCloverReport(PHP_CodeCoverage $coverage)
    {
        $writer = new PHP_CodeCoverage_Report_Clover();
        $writer->process($coverage, $this->getPath('clover.xml'));
    }

    /**
     * Writes the html report
     *
     * @param PHP_CodeCoverage $coverage
     */
    private function writeHtmlReport(PHP_CodeCoverage $coverage)
    {
        $writer = new \PHP_CodeCoverage_Report_HTML();
        $writer->process($coverage, $this->getPath('code-coverage'));
    }

    /**
     * Runs php spec unit tests
     */
    private function runPhpSpec()
    {
        $input = new ArgvInput(['phpspec', 'run', '--format=pretty']);
        $app = new Application(null);
        $app->setAutoExit(false);

        $this->exitCode = $app->run($input, new ConsoleOutput());
    }

    /**
     * @return PHP_CodeCoverage_Filter
     */
    private function getFilter()
    {
        $filter = new PHP_CodeCoverage_Filter();

        $filter->addDirectoryToWhitelist(realpath($this->baseDir.'../src'));
        $filter->addDirectoryToBlacklist(realpath($this->baseDir.'../bin'));
        $filter->addDirectoryToBlacklist(realpath($this->baseDir.'../spec'));
        $filter->addDirectoryToBlacklist(realpath($this->baseDir.'../vendor'));

        return $filter;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function getPath($path)
    {
        return $this->baseDir.$path;
    }
}