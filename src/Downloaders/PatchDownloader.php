<?php

declare(strict_types=1);

namespace WBCUpdater\Downloaders;

use WBCUpdater\Archives\PatchInterface;

interface PatchDownloader
{
    /**
     * @param PatchLinkInterface $link
     *
     * @return PatchInterface
     */
    public function download(PatchLinkInterface $link): PatchInterface;

    /**
     * @param callable $display
     */
    public function setStatusDisplay(callable $display): void;
}
