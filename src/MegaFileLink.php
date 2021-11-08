<?php

declare(strict_types=1);

namespace WBCUpdater;

final class MegaFileLink implements PatchLinkInterface
{
    /** @var string */
    private string $link;

    /**
     * MegaFileLink constructor.
     *
     * @param string $link
     */
    public function __construct(string $link)
    {
        $this->link = $link;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return (bool) preg_match('~^https://mega.nz/file/.+~', $this->link);
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'mega.nz';
    }

    /**
     * @param string $string
     *
     * @return PatchLinkInterface
     */
    public static function createFromString(string $string): PatchLinkInterface
    {
        return new self($string);
    }
}
