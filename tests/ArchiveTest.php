<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WBCUpdater\Archives\RarPatch;
use WBCUpdater\Archives\ZipPatch;

final class ArchiveTest extends TestCase
{
    public function testRarArchive(): void
    {
        $archive = new SplFileInfo(__DIR__ . '/data/test_document.rar');
        $patch = new RarPatch($archive);
        foreach ($patch->getFiles() as $file) {
            self::assertEquals($file->getCrc32(),
                dechex(crc32(file_get_contents(__DIR__ . '/data/test_document.txt'))));
        }
    }

    public function testZipArchive(): void
    {
        $archive = new SplFileInfo(__DIR__ . '/data/test_document.zip');
        $patch = new ZipPatch($archive);
        foreach ($patch->getFiles() as $file) {
            self::assertEquals($file->getCrc32(),
                dechex(crc32(file_get_contents(__DIR__ . '/data/test_document.txt'))));
        }
    }
}
