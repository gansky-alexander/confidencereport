<?php

namespace App\Service;

class ExcelWriter
{

    public function prepareExcel($fileName, array $jiraInformation)
    {
        $excel = new \PHPExcel();

        $excel->createSheet();
        $excel->setActiveSheetIndex(0);
        $excel->getActiveSheet()->setTitle('Confidence report');

        $worksheet = $excel->getActiveSheet();

        $worksheet->getColumnDimension('A')->setWidth(35);
        $worksheet->getColumnDimension('B')->setWidth(15);
        $worksheet->getColumnDimension('C')->setWidth(10);
        $worksheet->getColumnDimension('D')->setWidth(20);
        $worksheet->getColumnDimension('E')->setWidth(25);
        $worksheet->getColumnDimension('F')->setWidth(8);
        $worksheet->getColumnDimension('G')->setWidth(5);
        $worksheet->getColumnDimension('H')->setWidth(30);
        $worksheet->getColumnDimension('I')->setWidth(15);
        $worksheet->getColumnDimension('J')->setWidth(15);

        $i = 1;
        $worksheet->setCellValue("A$i", 'Epic');
        $worksheet->setCellValue("B$i", 'Epic status');
        $worksheet->setCellValue("C$i", '#');
        $worksheet->setCellValue("D$i", 'Title');
        $worksheet->setCellValue("E$i", 'Assigned');
        $worksheet->setCellValue("F$i", '% done');
        $worksheet->setCellValue("G$i", 'Conf.');
        $worksheet->setCellValue("H$i", 'Comment');
        $worksheet->setCellValue("I$i", 'TL Action Item');
        $worksheet->setCellValue("J$i", 'Comment (delete)');
        $worksheet->getStyle("A1:J1")->getFont()->setBold(true);

        $i++;

        foreach ($jiraInformation as $epic) {

            $epicComment = $this->prepareCommentForEpic($epic);

            $start = $i;
            $worksheet->setCellValue("A$i", $epic['issueKey'] . ' (' . $epic['summary'] . ')');
            $worksheet->setCellValue("B$i", $epic['status']);
            $worksheet->setCellValue("J$i", $epicComment);

            foreach ($epic['stories'] as $story) {
                $comment = $this->prepareCommentForStory($story);
                $worksheet->setCellValue("C$i", $story['issueKey']);
                $worksheet->setCellValue("D$i", $story['summary']);
                $worksheet->setCellValue("E$i", $story['assignee']);
                $worksheet->setCellValue("F$i", $story['percentage']);
                $worksheet->setCellValue("G$i", $story['confidence']);
                $worksheet->setCellValue("H$i", $comment);
                $worksheet->setCellValue("I$i", 'Myself');

                if ($story['confidence'] == 10) {
                    $worksheet->getStyle("G$i")->applyFromArray(
                        array(
                            'fill' => array(
                                'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                                'color' => array('rgb' => 'B7E1CD'),
                            )
                        )
                    );
                }

                $i++;
            }

            $worksheet->mergeCells("A$start:A" . ($i - 1));
            $worksheet->mergeCells("B$start:B" . ($i - 1));
        }

        $writer = new \PHPExcel_Writer_Excel2007($excel);

        $writer->save($fileName);
    }

    /**
     * Prepare comment for me to check
     *
     * @param $storyData
     * @return string
     */
    private function prepareCommentForStory($storyData)
    {
        if($storyData['percentage'] == 100) {
            return '';
        }

        $comment = '';

        $timeLogged = $storyData['subtaskTotal']['timeLogged'];
        $timeEstimated = $storyData['subtaskTotal']['timeEstimated'];
        $allCount = $storyData['subtaskTotal']['allCount'];
        $closed = $storyData['subtaskTotal']['closed'];

        if($timeLogged > $timeEstimated) {
            $comment .= 'Залогано больше времени чем оценка.' . PHP_EOL;
        }

        if($closed > $allCount) {
           $leftTasks =  $allCount - $closed;
           $leftTime = $timeEstimated - $timeLogged;

           $timeForTask = $timeLogged / $closed;
           if($leftTasks * $timeForTask > $leftTime) {
               $comment .= 'Есть риск закрыть историю с превышением бюджета.' . PHP_EOL;
           }
        }

        return $comment;
    }

    /**
     * Prepare comment for epic
     *
     * @param $epicData
     * @return string
     */
    private function prepareCommentForEpic($epicData)
    {
        return 'test test';
    }
}