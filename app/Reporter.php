<?php

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use DateTime;
use function array_key_exists;
use function file_get_contents;
use function str_replace;
use function var_dump;

define("DIAGNOSTIC", 1);
define("PROGRESS", 2);
define("FEEDBACK", 3);

class Reporter extends Command {
    protected static $defaultName = 'generate';
    protected $students;
    protected $assesmentsData;
    protected $studentResponses;
    protected $questions;

    protected function execute(InputInterface $input, OutputInterface $output): int {
    
        $io = new SymfonyStyle($input, $output);
        
        $studentId = $io->ask("Student ID");
        $reportType = (int) $io->ask("Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)");


        // load data files.
        $this->loadDataFiles();

        // validate student exsits
        $student = $this->getStudent($studentId);
        if (empty($student) || is_null($student)) {
            $io->error("invalid student id");
            return Command::INVALID;
        }

        switch ($reportType) {
            case DIAGNOSTIC:
                $this->diagnosticReport($student, $io);
                break;
            case PROGRESS:
                break;  
            case FEEDBACK:
                break;
            default:
                $io->error("Invalid report type. Supported types are 1 for Diagnostic, 2 for Progress, 3 for Feedback");
        }

        return Command::SUCCESS;
    }

    private function diagnosticReport(array $student, SymfonyStyle $io) {
        $resp = $this->getLastCompletedResponse($student["id"]);
        
        // consideration: validate datetime.
        $date = str_replace("/", "-", $resp['completed']);
        $date = new DateTime($date);
        $dateString = $date->format("dS F Y h:i A");

        $io->text(
            sprintf(
                "%s %s recently completed Numeracy assessment on %s", 
                $student["firstName"], 
                $student["lastName"], 
                $dateString, 
            )
        );
        
        $io->text(
            sprintf(
                "He got %d questions right out of %d. Details by strand given below:",
                $resp["results"]["rawScore"], 
                count($resp["responses"])
            )
        );
            
        $io->text("");
        
        $strandResults = $this->getStrandResults($student["id"], $resp);
        foreach ($strandResults as $k => $res) {
            $io->text(
                sprintf(
                    "%s: %d out of %d correct",
                    ucwords($k),
                    $res["count"],
                    $res["total"]
                )
            );
        }
    }

    // loadDataFiles will load all the files in to memory. 
    // 
    // considerations to take would be how large can these files get? And putting in place some
    // sort of validation against the size and types.
    private function loadDataFiles() {
        $studentsData = file_get_contents("./data/students.json");
        $this->students = json_decode($studentsData, true);

        $assesmentsData = file_get_contents("./data/assessments.json");
        $this->assesmentsData = json_decode($assesmentsData, true);
            
        $studentResponsesData = file_get_contents("./data/student-responses.json");
        $this->studentResponses = json_decode($studentResponsesData, true);

        $questionData = file_get_contents("./data/questions.json");
        $qs = json_decode($questionData, true);
        foreach ($qs as $q) {
            $this->questions[$q["id"]] = $q;
        }
    
    }

    private function getStudent($id): array {
       foreach ($this->students as $s) {
            if ($id == $s['id']) {
                return $s;
            }
        } 

        return [];
    }

    private function getStudentResponses($studentId): array {
        $resp = [];
        foreach ($this->studentResponses as $r) {
            // TODO: add validation here to see if student exists in the json.
            if ($studentId == $r['student']['id']) {
                array_push($resp, $r);
            }
        }

        return $resp;
    }

    private function getLastCompletedResponse($studentId): array {
        $resps = $this->getStudentResponses($studentId);

        $resps = array_filter($resps, function($v) {
            return array_key_exists("completed", $v);
        });     

        // consideration: validate the datetime format instead of replacing the bad char.
        usort($resps, function($a, $b) {
           $aCompleted = str_replace("/", "-", $a["completed"]);
           $bCompleted = str_replace("/", "-", $b["completed"]);
           return (strtotime($aCompleted) > strtotime($bCompleted)) ? -1 : 1;
        });

        return $resps[0];
    }   

    private function getStrandResults($studentId, $response) {
        $strands = [];    
        foreach($response["responses"] as $r) {
            $q = $this->questions[$r["questionId"]];
            
            if (!array_key_exists($q["strand"], $strands)) {
                $strands[$q["strand"]] = ["count" => 0, "total" => 0];
            }

            $correct = $q["config"]["key"] == $r["response"];

            if ($correct) {
                 $strands[$q["strand"]]["count"]++;       
            }
            
            $strands[$q["strand"]]["total"]++;       
        }

        return $strands;    
    }
}



