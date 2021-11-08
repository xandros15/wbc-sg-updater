<?php

declare(strict_types=1);

namespace WBCUpdater;

use Psr\Log\LoggerInterface;
use SplFileInfo;

final class GamePatcher
{
    /** @var PatchInterface */
    private PatchInterface $patch;
    /** @var Game */
    private Game $game;
    /** @var GameOverrider */
    private GameOverrider $overrider;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * GamePatcher constructor.
     *
     * @param PatchInterface $patch
     * @param Game $game
     * @param GameOverrider $overrider
     * @param LoggerInterface $logger
     */
    public function __construct(
        PatchInterface $patch,
        Game $game,
        GameOverrider $overrider,
        LoggerInterface $logger
    ) {
        $this->patch = $patch;
        $this->game = $game;
        $this->overrider = $overrider;
        $this->logger = $logger;
    }

    public function patch(): void
    {
        $dryRun = false;
        foreach ($this->patch->getFiles() as $file) {
            if ($this->canUpdate($file)) {
                echo 'Updating ' . $file->getName();
                if ($dryRun || $file->extract($this->game->getDirectory())) {
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

    /**
     * @param PatchFile $remote
     *
     * @return bool
     */
    private function canUpdate(PatchFile $remote): bool
    {
        if (!$this->overrider->canOverride($remote->getName())) {
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
