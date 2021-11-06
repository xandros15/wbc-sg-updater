<?php

declare(strict_types=1);

namespace WBCUpdater;

use SplFileInfo;

interface PatchDownloader
{
    public function download(): void;

    /**
     * @return SplFileInfo|null
     */
    public function getDownloadedFile(): ?SplFileInfo;
}
