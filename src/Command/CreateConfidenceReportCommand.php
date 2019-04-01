<?php

namespace App\Command;

use App\Service\ExcelWriter;
use App\Service\JiraReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateConfidenceReportCommand extends Command
{


    protected static $defaultName = 'app:create-confidence-report';

    /**
     * @var JiraReader
     */
    private $jiraReader;

    /**
     * @var ExcelWriter
     */
    private $excelWriter;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @param JiraReader $jiraReader
     */
    public function setJiraReader(JiraReader $jiraReader)
    {
        $this->jiraReader = $jiraReader;
    }

    /**
     * @param ExcelWriter $excelWriter
     */
    public function setExcelWriter(ExcelWriter $excelWriter)
    {
        $this->excelWriter = $excelWriter;
    }

    /**
     * @param string $projectDir
     */
    public function setProjectDir($projectDir)
    {
        $this->projectDir = $projectDir;
    }

    protected function configure()
    {
        $this
            ->addArgument('sprint_number', InputArgument::REQUIRED, 'Sprint number')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sprintNumber = $input->getArgument('sprint_number');

        $fileName = $this->projectDir . '/confidence' . date('Y-m-d') . '.xlsx';

        $list = $this->jiraReader->readStories($sprintNumber);

        $this->excelWriter->prepareExcel($fileName, $list);

        $output->writeln("Confidence report generated. Check the file '$fileName''");
    }
}