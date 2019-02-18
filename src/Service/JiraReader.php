<?php

namespace App\Service;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;

class JiraReader
{

    private $issueService;

    public function __construct($jiraHost, $jiraUser, $jiraPassword)
    {
        $this->issueService = new IssueService(
            new ArrayConfiguration(
                array(
                    'jiraHost' => $jiraHost,
                    'jiraUser' => $jiraUser,
                    'jiraPassword' => $jiraPassword,
                )
            )
        );
    }

    public function readStories()
    {
        $jql = 'project = PS AND issuetype in (Story) AND Sprint = 175';
        $issues = $this->issueService->search($jql);

        $ret = [];

        /** @var Issue $issue */
        foreach ($issues->issues as $issue) {
            $ret[] = [
                'issueKey' => $issue->key,
                'status' => $issue->fields->status->name,
                'type' => $issue->fields->issuetype->name,
                'assignee' => $issue->fields->assignee->displayName,
                'epicKey' => $issue->fields->customfield_10008,
            ];

            break;
        }

        print_r($ret);

        die();
    }
}