<?php

declare(strict_types=1);

namespace WBCUpdater;

use SplFileInfo;

interface PatchDownloader
{
    /**
     * @param PatchLinkInterface $link
     */
    public function download(PatchLinkInterface $link): void;

    /**
     * @return SplFileInfo|null
     */
    public function getDownloadedFile(): ?SplFileInfo;

    /**
     * @param callable $display
     */
    public function setStatusDisplay(callable $display): void;
}
