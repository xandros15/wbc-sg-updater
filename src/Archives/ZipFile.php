<?php

declare(strict_types=1);

namespace WBCUpdater\Archives;

use ZipArchive;

final class ZipFile implements PatchFile
{
    /** @var ZipArchive */
    private ZipArchive $zip;
    /** @var string */
    private string $name;
    /** @var int */
    private int $crc;

    /**
     * ZipFile constructor.
     *
     * @param ZipArchive $zip
     * @param string $name
     * @param int $crc
     */
    public function __construct(ZipArchive $zip, string $name, int $crc)
    {
        $this->zip = $zip;
        $this->name = $name;
        $this->crc = $crc;
    }

    /**
     * @param string $dir
     * @param string $filename
     *
     * @return bool
     */
    public function extract(string $dir, string $filename = ''): bool
    {
        $name = $filename ?: $this->name;
        $directory = dirname($dir . DIRECTORY_SEPARATOR . $name);
        if (!is_dir($directory) && !@mkdir($directory, 0777, true)) {
            return false;
        }

        return (bool) file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $this->zip->getFromName($this->name));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCrc32(): string
    {
        return dechex($this->crc);
    }
}