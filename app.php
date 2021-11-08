<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;
use WBCUpdater\Config;
use WBCUpdater\PatchCommand;

require_once __DIR__ . '/vendor/autoload.php';

$config = new Config(__DIR__ . '/config.yaml');
$log = new Logger(
    'general',
    [new StreamHandler(__DIR__ . '/' . $config['log_file'], Logger::INFO)]
);

chdir(__DIR__);
$application = new Application('WBC patcher', '1.0.0');
$command = new PatchCommand($config, $log);
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
