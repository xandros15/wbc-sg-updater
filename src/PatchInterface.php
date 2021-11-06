<?php

declare(strict_types=1);

namespace WBCUpdater;

use Generator;

interface PatchInterface
{
    /**
     * @return Generator|PatchFile[]
     */
    public function getFiles(): Generator;
}
