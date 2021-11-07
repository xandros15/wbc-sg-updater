<?php

declare(strict_types=1);

namespace WBCUpdater;

use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

final class MegaPatchDownloader implements PatchDownloader
{
    /** @var string */
    private string $megatools;
    /** @var string */
    private string $downloadedFile;
    /** @var string */
    private string $link;
    /** @var Config */
    private Config $config;

    /**
     * MegaPatchDownloader constructor.
     *
     * @param string $link
     * @param string $megatools
     * @param Config $config
     */
    public function __construct(
        string $link,
        string $megatools,
        Config $config
    ) {
        $realpath = realpath($megatools);
        if (!$realpath) {
            throw new RuntimeException("Cannot find megatools in {$megatools}.");
        }
        $this->megatools = $realpath;
        $this->config = $config;
        $this->link = $link;
    }

    public function download(): void
    {
        $process = new Process([$this->megatools, 'dl', '--path', $this->config['tmp_dir'], $this->link]);
        $process->setTimeout(null);
        $process->run(function (string $stream, $payload) {
            if ($stream === Process::OUT) {
                echo $payload;
                if (preg_match('/^Downloaded\s(.*)/', $payload, $matches)) {
                    $this->downloadedFile = $this->config['tmp_dir'] . '/' . trim($matches[1]);
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
