<?php

declare(strict_types=1);

namespace WBCUpdater;

final class Game
{
    private const GAME_EXE = 'Battlecry III.exe';
    /** @var string */
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @return bool
     */
    public function removePatchXcr(): bool
    {
        if (file_exists($this->getDirectory() . '/PatchData.xcr')) {
            //soft delete
            return rename(
                $this->getDirectory() . '/PatchData.xcr',
                $this->getDirectory() . '/.PatchData.xcr'
            );
            //unlink($this->gameChecker->getDirectory() . '/.PatchData.xcr');
        }

        return false;
    }

    /**
     * @return bool
     */
    public function checkDirectory(): bool
    {
        return file_exists($this->directory . '/' . self::GAME_EXE);
    }

    /**
     * @param string $directory
     */
    public function changeDirectory(string $directory): void
    {
        $this->directory = $directory;
    }
}
