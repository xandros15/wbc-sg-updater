<?php

use Symfony\Component\Console\Application;
use WBCUpdater\Config;
use WBCUpdater\PatchCommand;

require_once __DIR__ . '/vendor/autoload.php';

$config = new Config(__DIR__ . '/config.yaml');
$application = new Application('WBC patcher', '1.0.0');
$command = new PatchCommand($config);
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
