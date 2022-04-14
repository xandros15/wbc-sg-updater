<?php

declare(strict_types=1);

namespace WBCUpdater\Archives;

use Generator;
use RarArchive;
use SplFileInfo;
use WBCUpdater\Exceptions\RuntimeException;

final class RarPatch implements PatchInterface
{
    private const EXTENSION = 'rar';

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
        $archive = $this->createArchive();
        foreach ($archive->getEntries() as $entry) {
            if ($entry && !$entry->isDirectory()) {
                yield new RarFile($entry);
            }
        }
        $archive->close();
    }

    /**
     * Workaround https://bugs.php.net/bug.php?id=75557
     *
     * @return RarArchive
     */
    private function createArchive(): RarArchive
    {
        if (preg_match('/[~a-z_\s!@#$%^&()+-=.,{}\[];\']+/i', $this->file->getBasename())) {
            return RarArchive::open($this->file->getRealPath());
        }

        $filename = $this->file->getPath() . '/' . uniqid();

        if (!copy($this->file->getRealPath(), $filename)) {
            throw new RuntimeException('Cannot make an temporary patch file.');
        }

        register_shutdown_function(fn () => @unlink($filename));

        return RarArchive::open($filename);
    }

    /**
     * @param string $extension
     *
     * @return bool
     */
    public static function isCorrectExtension(string $extension): bool
    {
        return $extension === self::EXTENSION;
    }
}
