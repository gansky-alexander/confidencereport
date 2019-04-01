<?php

namespace App\Service;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;

class JiraReader
{

    /**
     * @var IssueService
     */
    private $issueService;

    /**
     * @var array
     */
    private $preparedJiraResult = [];

    /**
     * @param string $jiraHost
     * @param string $jiraUser
     * @param string $jiraPassword
     */
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

    /**
     * Get all information for stories
     *
     * @return array
     */
    public function readStories($sprintNumber)
    {
        $jql = "project = PS AND issuetype in (Story) AND Sprint = $sprintNumber";
        $startAt = 0;
        $maxResult = 50;

        $issues = $this->issueService->search($jql, $startAt, $maxResult);
        $totalCount = $issues->total;

        $this->reformatJiraResult($issues->issues);

        $page = $totalCount / $maxResult;

        for ($startAt = 1; $startAt < $page; $startAt++) {
            $issues = $this->issueService->search($jql, $startAt, $maxResult);

            $this->reformatJiraResult($issues->issues);
        }

        return $this->preparedJiraResult;
    }

    /**
     * @param array $issues
     */
    private function reformatJiraResult($issues)
    {
        /** @var Issue $issue */
        foreach ($issues as $issue) {
            $queryParam = [
                'fields' => [  // default: '*all'
                    'summary',
                    'status',
                    'assignee',
                ],
            ];

            if (!isset($this->preparedJiraResult[$issue->fields->customfield_10008])) {
                $epic = $this->issueService->get($issue->fields->customfield_10008, $queryParam);

                $this->preparedJiraResult[$issue->fields->customfield_10008] = [
                    'stories' => [],
                    'issueKey' => $issue->fields->customfield_10008,
                    'summary' => $epic->fields->summary,
                    'status' => $epic->fields->status->name,
                    'assignee' => $epic->fields->assignee->displayName,
                ];
            }

            $percentage = 0;
            switch (strtoupper($issue->fields->status->name)) {
                case 'NEW':
                case 'REQUIREMENTS SPECIFICATION':
                case 'ARCHITECTURE SPECIFICATION':
                case 'ONHOLD':
                case 'READY FOR DEVELOPMENT':
                case 'REOPEN':
                    $percentage = 0;
                    break;
                case 'IN PROGRESS':
                    $percentage = '???';
                    break;
                case 'IN CR':
                case 'IN PR':
                    $percentage = 70;
                    break;
                case 'READY FOR QA':
                case 'IN QA':
                case 'BUGFIXING':
                    $percentage = 80;
                    break;
                case 'QA DONE':
                case 'RESOLVED':
                    $percentage = 100;
                    break;
            }

            $confidence = 0;

            if ($percentage == 100) $confidence = 10;

            $subtaskTotal = [
                'allCount' => 0,
                'closed' => 0,
                'timeEstimated' => 0,
                'timeLogged' => 0,
            ];

            /** @var Issue $subtask */
            foreach ($issue->fields->subtasks AS $subtask) {
                $timeTracking = $this->issueService->getTimeTracking($issue->key);

                $subtaskTotal['allCount'] = $subtaskTotal['allCount'] + 1;
                if ($subtask->fields->status->name = 'Resolved') {
                    $subtaskTotal['closed'] = $subtaskTotal['closed'] + 1;
                }

                $subtaskTotal['timeEstimated'] = $subtaskTotal['timeEstimated'] + $timeTracking->getOriginalEstimateSeconds();
                $subtaskTotal['timeLogged'] = $subtaskTotal['timeLogged'] + $timeTracking->getTimeSpentSeconds();
            }

            $this->preparedJiraResult[$issue->fields->customfield_10008]['stories'][$issue->key] = [
                'issueKey' => $issue->key,
                'status' => $issue->fields->status->name,
                'assignee' => $issue->fields->assignee->displayName,
                'summary' => $issue->fields->summary,
                'percentage' => $percentage,
                'confidence' => $confidence,
                'subtaskTotal' => $subtaskTotal,
            ];

        }
    }
}