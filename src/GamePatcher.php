<?php

declare(strict_types=1);

namespace WBCUpdater;

use Psr\Log\LoggerInterface;
use SplFileInfo;
use WBCUpdater\Archives\PatchFile;
use WBCUpdater\Archives\PatchInterface;

final class GamePatcher
{
    /** @var PatchInterface[] */
    private array $patches = [];
    /** @var Game */
    private Game $game;
    /** @var GameOverrider|null */
    private ?GameOverrider $overrider = null;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * GamePatcher constructor.
     *
     * @param Game $game
     * @param LoggerInterface $logger
     */
    public function __construct(Game $game, LoggerInterface $logger)
    {
        $this->game = $game;
        $this->logger = $logger;
    }

    /**
     * @param bool $dryrun
     */
    public function patch($dryrun = false): void
    {
        foreach ($this->patches as $patch) {
            foreach ($patch->getFiles() as $file) {
                if ($this->canUpdate($file)) {
                    echo 'Updating ' . $file->getName();
                    if ($dryrun || $file->extract($this->game->getDirectory())) {
                        echo '... done';
                        $this->logger->info("Updated {$file->getName()} ({$file->getCrc32()})");
                    } else {
                        echo '... failed';
                        $this->logger->warning("Failed {$file->getName()} ({$file->getCrc32()})");
                    }
                    echo PHP_EOL;
                } else {
                    $this->logger->info("Skip {$file->getName()} ({$file->getCrc32()})");
                }
            }
        }
    }

    /**
     * @param GameOverrider $overrider
     */
    public function setOverrider(GameOverrider $overrider)
    {
        $this->overrider = $overrider;
    }

    /**
     * @param PatchInterface $patch
     */
    public function add(PatchInterface $patch)
    {
        $this->patches[] = $patch;
    }

    /**
     * @param PatchFile $remote
     *
     * @return bool
     */
    private function canUpdate(PatchFile $remote): bool
    {
        if ($this->overrider && !$this->overrider->canOverride($remote->getName())) {
            return false;
        }

        $local = new SplFileInfo($this->game->getDirectory() . '/' . $remote->getName());

        return !$this->isSameFile($local, $remote);
    }

    /**
     * @param SplFileInfo $local
     * @param PatchFile $remote
     *
     * @return bool
     */
    private function isSameFile(SplFileInfo $local, PatchFile $remote): bool
    {
        return $local->isFile() && dechex(crc32(file_get_contents($local->getRealPath()))) === $remote->getCrc32();
    }
}
