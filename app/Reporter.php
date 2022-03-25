<?php

use Symfony\Component\Console\Style\SymfonyStyle;

class Reporter {
    
    protected $students;
    protected $studentResponses;
    protected $questions;

    public function __construct(array $students, array $studentResponses, array $questions) {
        $this->students = $students;
        $this->studentResponses = $studentResponses;
        $this->questions = $questions;
    } 

    protected function diagnosticReport(array $student, SymfonyStyle $io) {
        $resp = $this->getLastCompletedResponse($student["id"]);
        
        $dateString = $this->formatDate($res["completed"], "dS F Y h:i A");

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

    protected function progressReport(array $student, SymfonyStyle $io) {
        $resps = $this->getCompletedStudentResponses($student["id"]);
        $io->text(
            sprintf(
                "%s %s has completed Numeracy assessment %d times in total. Date and raw score given below:",
                $student["firstName"], 
                $student["lastName"], 
                count($resps),
            )
        );
        
        $io->text("");

        foreach($resps as $r) {
            $dateString = $this->formatDate($r["completed"], "dS F Y");
            
            $io->text(
                sprintf(
                    "Date: %s, Raw Score: %d out of %d",
                    $dateString,
                    $r["results"]["rawScore"], 
                    count($r["responses"])
                )
            );
        }
        
        $fr = $this->getFirstCompletedResponse($student["id"]);
        $er = $this->getLastCompletedResponse($student["id"]);

        $io->text("");
        $io->text(
            sprintf(
                "%s %s got %d more correct in the recent completed assessment than the oldest",
                $student["firstName"], 
                $student["lastName"],
                $r["results"]["rawScore"] - $fr["results"]["rawScore"] 
            )
        );
    }

    protected function feedbackReport(array $student, SymfonyStyle $io) {
        $r = $this->getLastCompletedResponse($student["id"]);
        
        $dateString = $this->formatDate($r["completed"], "dS F Y h:i A"); 

        $io->text(
            sprintf(
                "%s %s recently completed Numeracy assessment on %s",
                $student["firstName"], 
                $student["lastName"],   
                $dateString
            )
        );

        $io->text(
            sprintf(
                "He got %d questions right out of %d. Feedback for wrong answers given below",
                $r["results"]["rawScore"],
                count($r["responses"])
            )
        );

        $io->text("");

        $feedback = $this->buildFeedbackResults($student["id"], $r);
        foreach($feedback as $f) {
            $io->text(sprintf("Question: %s", $f["question"])); 
            $io->text(sprintf("Your answer: %s with value %s", $f["incorrect_label"], $f["incorrect_answer"]));
            $io->text(sprintf("Right answer: %s with value %s", $f["correct_label"], $f["correct_answer"]));
            $io->text(sprintf("Hint: %s", $f["hint"]));
            $io->text("");
        }
    }

    protected function getStudent($id): array {
       foreach ($this->students as $s) {
            if ($id == $s['id']) {
                return $s;
            }
        } 

        return [];
    }

    private function getCompletedStudentResponses($studentId): array {
        $resps = [];
        foreach ($this->studentResponses as $r) {
            // TODO: add validation here to see if student exists in the json.
            if ($studentId == $r['student']['id']) {
                array_push($resps, $r);
            }
        }
        
        $resps = array_filter($resps, function($v) {
            return array_key_exists("completed", $v);
        });     
        
        // consideration: validate the datetime format instead of replacing the bad char.
        usort($resps, function($a, $b) {
           $aCompleted = str_replace("/", "-", $a["completed"]);
           $bCompleted = str_replace("/", "-", $b["completed"]);
           return (strtotime($aCompleted) < strtotime($bCompleted)) ? -1 : 1;
        });

        return $resps;
    }

    private function getFirstCompletedResponse($studentId): array {
        $resps = $this->getCompletedStudentResponses($studentId);

        return reset($resps);
    }   
    
    private function getLastCompletedResponse($studentId): array {
        $resps = $this->getCompletedStudentResponses($studentId);

        return end($resps);
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

    private function buildFeedbackResults($studentId, $response) {
        $questions = [];
        foreach($response["responses"] as $r) {
            $el = [];    
            $q = $this->questions[$r["questionId"]];
            
            $correct = $q["config"]["key"] == $r["response"];
            if ($correct) {
                continue;
            }

            foreach($q["config"]["options"] as $o) {
                if ($o["id"] == $q["config"]["key"]) {
                    $el["correct_answer"] = $o["value"];
                    $el["correct_label"] = $o["label"];
                }
                
                if ($o["id"] == $r["response"]) {
                    $el["incorrect_answer"] = $o["value"];
                    $el["incorrect_label"] = $o["label"];
                }
            }

            $el["hint"] = $q["config"]["hint"];
            $el["question"] = $q["stem"];
            
            array_push($questions, $el);
        }
    
        return $questions;
    }   

    // consideration: validate datetime.
    private function formatDate(string $timestamp, string $format): string {
        $date = str_replace("/", "-", $r['completed']);
        $date = new DateTime($date);

        return $date->format($format);
    }
}
