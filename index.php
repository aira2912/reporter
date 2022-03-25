#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use App\Reporter as Reporter;
use Symfony\Component\Console\Application;

$application = new Application();

$command = new Reporter();

$application->add($command);
$application->setDefaultCommand($command->getName());
$application->run();
