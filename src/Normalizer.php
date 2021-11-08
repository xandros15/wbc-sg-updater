<?php

declare(strict_types=1);

namespace WBCUpdater;

final class Normalizer
{
    /**
     * @param string $path
     *
     * @return string
     */
    public static function path(string $path): string
    {
        if (substr($path, 1, 1) !== ':') {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }

        return $path;
    }
}
