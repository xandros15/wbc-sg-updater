<?php

declare(strict_types=1);

namespace WBCUpdater;

use RarEntry;

final class RarFile implements PatchFile
{
    /** @var RarEntry */
    private RarEntry $entry;

    /**
     * RarFile constructor.
     *
     * @param RarEntry $entry
     */
    public function __construct(RarEntry $entry)
    {
        $this->entry = $entry;
    }

    /**
     * @param string $dir
     * @param string $filename
     *
     * @return bool
     */
    public function extract(string $dir, string $filename = ''): bool
    {
        return (bool) $this->entry->extract($dir, $filename);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->entry->getName();
    }

    /**
     * @return string
     */
    public function getCrc32(): string
    {
        return $this->entry->getCrc();
    }
}
