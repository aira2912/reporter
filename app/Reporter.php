<?php

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function file_get_contents;

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
        
        $name = $io->ask("Student ID");
        $reportType = (int) $io->ask("Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)");


        // load data files.
        $this->loadDataFiles();

        switch ($reportType) {
            case DIAGNOSTIC:
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


    // loadDataFiles will load all the files in to memory. 
    // 
    // consideration to take would be how large can these files get? And putting in place some
    // sort of validation against the size and types.
    private function loadDataFiles() {
        $studentsData = file_get_contents("./data/students.json");
        $this->students = json_decode($studentsData, true);

        $assesmentsData = file_get_contents("./data/assesments.json");
        $this->assesmentsData = json_decode($assesmentsData, true);
            
        $studentResponsesData = file_get_contents("./data/student-responses.json");
        $this->studentResponses = json_decode($studentResponsesData, true);

        $questionData = file_get_contents("./data/questions.json");
        $this->questions = json_decode($questionData, true);
    }

    private function getStudent($id): array {
       foreach ($this->students as $s) {
            if ($id == $s['id']) {
                return $student;
            }
        } 

        return [];
    }
}



