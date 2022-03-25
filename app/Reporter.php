<?php

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

define("DIAGNOSTIC", 1);
define("PROGRESS", 2);
define("FEEDBACK", 3);

class Reporter extends Command {
    protected static $defaultName = 'generate';

    protected function execute(InputInterface $input, OutputInterface $output): int {
    
        $io = new SymfonyStyle($input, $output);
        
        $name = $io->ask("Student ID");
        $reportType = (int) $io->ask("Report to generate (1 for Diagnostic, 2 for Progress, 3 for Feedback)");

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
}


