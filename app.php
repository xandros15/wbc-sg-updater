<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
$dispatcher = new EventDispatcher();
$dispatcher->addListener(ConsoleEvents::ERROR, fn (ConsoleErrorEvent $event) => $log->error($event->getError()));
$application->setDispatcher($dispatcher);
$command = new PatchCommand($config, $log);
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
