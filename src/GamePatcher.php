<?php

namespace WBCUpdater;

use Psr\Log\LoggerInterface;
use RarArchive;
use RarEntry;
use SplFileInfo;

final class GamePatcher
{
    /** @var SplFileInfo */
    private SplFileInfo $patch;
    /** @var Game */
    private Game $game;
    /** @var GameOverrider */
    private GameOverrider $overrider;
    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /**
     * GamePatcher constructor.
     *
     * @param PatchDownloader $downloader
     * @param Game $game
     * @param GameOverrider $overrider
     * @param LoggerInterface $logger
     */
    public function __construct(
        PatchDownloader $downloader,
        Game $game,
        GameOverrider $overrider,
        LoggerInterface $logger
    ) {
        $this->patch = $downloader->getDownloadedFile();
        $this->game = $game;
        $this->overrider = $overrider;
        $this->logger = $logger;
    }

    public function patch(): void
    {
        $dryRun = false;
        $archive = RarArchive::open($this->patch->getRealPath());
        foreach ($archive->getEntries() as $entry) {
            if ($this->canUpdate($entry)) {
                echo 'Updating ' . $entry->getName();
                if ($dryRun || $entry->extract($this->game->getDirectory())) {
                    echo '... done';
                    $this->logger->info("Updated {$entry->getName()} ({$entry->getCrc()})");
                } else {
                    echo '... failed';
                    $this->logger->warning("Failed {$entry->getName()} ({$entry->getCrc()})");
                }
                echo PHP_EOL;
            } else {
                $this->logger->info("Skip {$entry->getName()} ({$entry->getCrc()})");
            }
        }
        $archive->close();
    }

    /**
     * @param RarEntry $entry
     *
     * @return bool
     */
    private function canUpdate(RarEntry $entry): bool
    {
        if ($entry->isDirectory() || !$this->overrider->canOverride($entry->getName())) {
            return false;
        }

        $existing = new SplFileInfo($this->game->getDirectory() . '/' . $entry->getName());

        return (bool) $existing->getRealPath() && $existing->isFile() && !$this->isSameFile($existing, $entry);
    }

    /**
     * @param SplFileInfo $exist
     * @param RarEntry $new
     *
     * @return bool
     */
    private function isSameFile(SplFileInfo $exist, RarEntry $new): bool
    {
        return $exist->isFile() && dechex(crc32(file_get_contents($exist->getRealPath()))) === $new->getCrc();
    }
}
