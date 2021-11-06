<?php

namespace WBCUpdater;

use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

final class MegaPatchDownloader implements PatchDownloader
{
    /** @var string */
    private string $megatools;
    /** @var string */
    private string $temp;
    /** @var string */
    private string $downloadedFile;
    /** @var string */
    private string $link;

    /**
     * MegaPatchDownloader constructor.
     *
     * @param string $link
     * @param string $megatools
     * @param string $temp
     */
    public function __construct(
        string $link,
        string $megatools,
        string $temp = __DIR__ . '/../tmp'
    ) {
        $realpath = realpath($megatools);
        if (!$realpath) {
            throw new RuntimeException("Cannot find megatools in {$megatools}.");
        }
        $this->megatools = $realpath;
        $this->temp = $temp;
        $this->link = $link;
    }

    public function download(): void
    {
        $process = new Process([$this->megatools, 'dl', '--path', $this->temp, $this->link]);
        $process->setTimeout(null);
        $process->run(function (string $stream, $payload) {
            if ($stream === Process::OUT) {
                echo $payload;
                if (preg_match('/^Downloaded\s(.*)/', $payload, $matches)) {
                    $this->downloadedFile = $this->temp . '/' . trim($matches[1]);
                }
            }
        });

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            if (preg_match('~.*Local file already exists: (.+)~', $error, $matches)) {
                $this->downloadedFile = trim($matches[1]);
                throw new FileExistException($error);
            } else {
                throw new RuntimeException($error);
            }
        }
    }

    /**
     * @return SplFileInfo|null
     */
    public function getDownloadedFile(): ?SplFileInfo
    {
        return isset($this->downloadedFile) ? new SplFileInfo($this->downloadedFile) : null;
    }
}
