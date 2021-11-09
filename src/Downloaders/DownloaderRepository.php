<?php

declare(strict_types=1);

namespace WBCUpdater\Downloaders;

use WBCUpdater\Exceptions\ConfigurationException;
use WBCUpdater\Exceptions\RuntimeException;

/**
 * @todo refactor this class
 * Class DownloaderRepository
 * @package WBCUpdater\Downloaders
 */
final class DownloaderRepository
{
    /** @var array */
    private array $type = [];

    /**
     * @param string $linkType
     * @param PatchDownloader $downloader
     */
    public function register(string $linkType, PatchDownloader $downloader)
    {
        if (!is_subclass_of($linkType, PatchLinkInterface::class)) {
            throw new ConfigurationException(sprintf(
                'Class "%s" does not implement "%s".',
                $linkType,
                PatchLinkInterface::class
            ));
        }
        $this->type[$linkType] = $downloader;
    }

    /**
     * @param PatchLinkInterface $link
     *
     * @return PatchDownloader
     */
    public function getDownloader(PatchLinkInterface $link): PatchDownloader
    {
        $linkClass = get_class($link);
        if (!isset($this->type[$linkClass])) {
            throw new RuntimeException("{$linkClass} doesn't register.");
        }

        return $this->type[$linkClass];
    }
}
