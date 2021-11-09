<?php

declare(strict_types=1);

namespace WBCUpdater\Downloaders;

interface PatchLinkInterface
{
    /**
     * @return string
     */
    public function getLink(): string;

    /**
     * @return bool
     */
    public function isValid(): bool;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $string
     *
     * @return PatchLinkInterface
     */
    public static function createFromString(string $string): PatchLinkInterface;
}
