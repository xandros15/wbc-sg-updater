<?php

declare(strict_types=1);

namespace WBCUpdater\Archives;

use Generator;
use RarArchive;
use SplFileInfo;

final class RarPatch implements PatchInterface
{
    /** @var SplFileInfo */
    private SplFileInfo $file;

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * @return Generator|PatchFile[]
     */
    public function getFiles(): Generator
    {
        $archive = RarArchive::open($this->file->getRealPath());
        foreach ($archive->getEntries() as $entry) {
            if ($entry && !$entry->isDirectory()) {
                yield new RarFile($entry);
            }
        }
        $archive->close();
    }
}
