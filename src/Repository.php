<?php

declare(strict_types=1);

namespace WBCUpdater;

use WBCUpdater\Exceptions\ConfigurationException;
use WBCUpdater\Exceptions\RuntimeException;

final class Repository
{
    /** @var string */
    private string $repository;

    /**
     * Repository constructor.
     *
     * @param string $repository URL for repository (pastebin)
     */
    public function __construct(string $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $class ClassName that's PatchLinkInterface
     *
     * @return PatchLinkInterface
     */
    public function getPatchLink(string $class): PatchLinkInterface
    {
        if (!is_subclass_of($class, PatchLinkInterface::class)) {
            throw new ConfigurationException(sprintf(
                'Class "%s" does not implement "%s".',
                $class,
                PatchLinkInterface::class
            ));
        }

        $link = (string) file_get_contents($this->repository);
        if (!$link) {
            throw new RuntimeException("Cannot get link from repository: {$this->repository}.");
        }

        /** @var $class PatchLinkInterface */
        return $class::createFromString($link);
    }
}
