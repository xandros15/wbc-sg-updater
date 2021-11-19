<?php

declare(strict_types=1);

namespace WBCUpdater\Archives;

use Generator;
use SplFileInfo;
use ZipArchive;

final class ZipPatch implements PatchInterface
{
    private const EXTENSION = 'zip';

    /** @var SplFileInfo */
    private SplFileInfo $file;

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
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

    /**
     * @return Generator|PatchFile[]
     */
    public function getFiles(): Generator
    {
        $zip = new ZipArchive();
        $zip->open($this->file->getRealPath());
        for ($idx = 0; $zipFile = $zip->statIndex($idx); $idx++) {
            if ($zipFile['size'] > 0) {//is file
                yield new ZipFile($zip, $zipFile['name'], $zipFile['crc']);
            }
        }
        $zip->close();
    }
}
