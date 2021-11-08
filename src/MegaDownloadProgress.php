<?php

declare(strict_types=1);

namespace WBCUpdater;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

final class MegaDownloadProgress
{
    private const MAX = 100;
    private const FORMAT = '[%bar%] %percent:3s%%';
    /** @var ProgressBar */
    private ProgressBar $progress;

    /**
     * MegaDownloadProgress constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->progress = new ProgressBar($output, self::MAX);
        $this->progress->setFormat(self::FORMAT);
    }

    /**
     * @return callable
     */
    public function getCallable(): callable
    {
        return function ($data) {
            $data = trim($data);
            if (preg_match('~^.+:\s(\d{1,3}),\d{2}~', $data, $matches)) {
                $this->progress->setProgress((int) $matches[1]);
            }
        };
    }

    public function finish(): void
    {
        $this->progress->finish();
    }

    public function start(): void
    {
        $this->progress->start();
    }
}