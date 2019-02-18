<?php

namespace App\Command;

use App\Service\JiraReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateConfidenceReportCommand extends Command
{

    /**
     * @var JiraReader
     */
    private $jiraReader;
public function setJiraReader($jiraReader)
{
 $this->jiraReader = $jiraReader;
}

    protected static $defaultName = 'app:create-confidence-report';



    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $list = $this->jiraReader->readStories();

        $output->writeln($list);
    }
}