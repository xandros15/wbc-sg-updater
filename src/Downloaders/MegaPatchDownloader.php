<?php

declare(strict_types=1);

namespace WBCUpdater\Downloaders;

use Monolog\Logger;
use SplFileInfo;
use Symfony\Component\Process\Process;
use WBCUpdater\Archives\PatchInterface;
use WBCUpdater\Archives\RarPatch;
use WBCUpdater\Exceptions\ConfigurationException;
use WBCUpdater\Exceptions\InputException;
use WBCUpdater\Exceptions\RuntimeException;

final class MegaPatchDownloader implements PatchDownloader
{
    /** @var string */
    private string $megatools;
    /** @var string */
    private string $tmpDir;
    /** @var Logger */
    private Logger $logger;
    /** @var callable */
    private $displayStatus;

    /**
     * MegaPatchDownloader constructor.
     *
     * @param string $megatools
     * @param string $tmpDir
     * @param Logger $logger
     */
    public function __construct(
        string $megatools,
        string $tmpDir,
        Logger $logger
    ) {
        $realpath = realpath($megatools);
        if (!$realpath) {
            throw new ConfigurationException("Cannot find megatools in {$megatools}.");
        }

        $this->megatools = $realpath;
        $this->tmpDir = $tmpDir;
        $this->logger = $logger;
        $this->setStatusDisplay(function (string $data) {
            echo $data;
        });
    }

    /**
     * @param PatchLinkInterface $link
     *
     * @return PatchInterface
     */
    public function download(PatchLinkInterface $link): PatchInterface
    {
        if (!$link->isValid()) {
            throw new InputException(sprintf('Link should be valid %s link to file.', $link->getName()));
        }
        $process = new Process([$this->megatools, 'dl', '--path', $this->tmpDir, $link->getLink()]);
        $process->setTimeout(null);
        $process->run(function (string $stream, $payload) use (&$file) {
            if ($stream === Process::OUT) {
                ($this->displayStatus)($payload);
                $this->logger->info($payload);
                if (preg_match('/^Downloaded\s(.*)/', $payload, $matches)) {
                    $file = new SplFileInfo($this->tmpDir . '/' . trim($matches[1]));
                }
            }
        });

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            if (preg_match('~.*Local file already exists: (.+)~', $error, $matches)) {
                $file = new SplFileInfo(trim($matches[1]));
                $this->logger->warning(trim($error));
            } else {
                $this->logger->error(trim($error));
                throw new RuntimeException($error);
            }
        }

        if (!$file) {
            throw new InputException('Cannot find patch file.');
        }
        $builder = new PatchFactory();
        $builder->register(RarPatch::class);

        return $builder->create($file);
    }

    /**
     * @param callable $display
     */
    public function setStatusDisplay(callable $display): void
    {
        $this->displayStatus = $display;
    }
}
