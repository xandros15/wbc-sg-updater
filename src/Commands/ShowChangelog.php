<?php

declare(strict_types=1);

namespace WBCUpdater\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class ShowChangelog implements CommandInterface
{
    /** @var string */
    private string $googleDocumentLink;

    public function __construct(string $googleDocumentLink)
    {
        $this->googleDocumentLink = $googleDocumentLink;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Command $command
     */
    public function execute(InputInterface $input, OutputInterface $output, Command $command): void
    {
        $process = new Process([
            'explorer',
            $this->googleDocumentLink,
        ]);
        $process->run();
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    public function setOption(string $name, $value): void
    {
    }

    /**
     * @return array
     */
    public function getQuestions(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return 'Show Changelog';
    }
}
