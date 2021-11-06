<?php

declare(strict_types=1);

namespace WBCUpdater;

interface PatchFile
{
    /**
     * @param string $dir
     * @param string $filename
     *
     * @return bool
     */
    public function extract(string $dir, string $filename = ''): bool;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getCrc32(): string;
}
