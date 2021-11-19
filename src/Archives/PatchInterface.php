<?php

declare(strict_types=1);

namespace WBCUpdater\Archives;

use Generator;

interface PatchInterface
{
    /**
     * @return Generator|PatchFile[]
     */
    public function getFiles(): Generator;

    /**
     * @param string $extension
     *
     * @return bool
     */
    public static function isCorrectExtension(string $extension): bool;
}
