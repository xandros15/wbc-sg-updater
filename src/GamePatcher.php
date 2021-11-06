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
     * @param PatchFile $file
     *
     * @return bool
     */
    private function canUpdate(PatchFile $file): bool
    {
        if (!$this->overrider->canOverride($file->getName())) {
            return false;
        }

        $existing = new SplFileInfo($this->game->getDirectory() . '/' . $file->getName());

        return (bool) $existing->getRealPath() && $existing->isFile() && !$this->isSameFile($existing, $file);
    }

    /**
     * @param SplFileInfo $exist
     * @param PatchFile $new
     *
     * @return bool
     */
    private function isSameFile(SplFileInfo $exist, PatchFile $new): bool
    {
        return $exist->isFile() && dechex(crc32(file_get_contents($exist->getRealPath()))) === $new->getCrc32();
    }
}
