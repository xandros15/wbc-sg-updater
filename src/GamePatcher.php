<?php

namespace WBCUpdater;

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

    /**
     * GamePatcher constructor.
     *
     * @param PatchDownloader $downloader
     * @param Game $game
     * @param GameOverrider $overrider
     */
    public function __construct(
        PatchDownloader $downloader,
        Game $game,
        GameOverrider $overrider
    ) {
        $this->patch = $downloader->getDownloadedFile();
        $this->game = $game;
        $this->overrider = $overrider;
    }

    public function patch(): void
    {
        $dryRun = true;
        $archive = RarArchive::open($this->patch->getRealPath());
        foreach ($archive->getEntries() as $entry) {
            if ($this->canUpdate($entry)) {
                echo 'Updating ' . $entry->getName();
                if ($dryRun || $entry->extract($this->game->getDirectory())) {
                    echo '... done';
                } else {
                    echo '... failed';
                }
                echo PHP_EOL;
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
