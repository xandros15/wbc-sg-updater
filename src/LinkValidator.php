<?php

declare(strict_types=1);

namespace WBCUpdater;

use WBCUpdater\Exceptions\InputException;

final class LinkValidator
{
    /**
     * @param PatchLinkInterface $link
     *
     * @return string
     */
    public static function assert(PatchLinkInterface $link): string
    {
        if (!$link->isValid()) {
            throw new InputException(sprintf('Link should be valid %s link to file.', $link->getName()));
        }

        return $link->getLink();
    }
}
