<?php

declare(strict_types=1);

namespace WBCUpdater\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface CommandInterface
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Command $command
     */
    public function execute(InputInterface $input, OutputInterface $output, Command $command): void;

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption(string $name, $value): void;

    /**
     * @return array
     */
    public function getQuestions(): array;

    /**
     * @inheritDoc
     */
    public function __toString();
}
