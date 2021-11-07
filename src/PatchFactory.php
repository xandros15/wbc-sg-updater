<?php

declare(strict_types=1);

namespace WBCUpdater;

use SplFileInfo;
use WBCUpdater\Exceptions\ConfigurationException;

final class PatchFactory
{
    /** @var string */
    private string $class;

    /**
     * PatchBuilder constructor.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        if (!is_subclass_of($class, PatchInterface::class)) {
            throw new ConfigurationException(sprintf(
                'Class "%s" does not implement "%s".',
                $class,
                PatchInterface::class
            ));
        }

        $this->class = $class;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return PatchInterface
     */
    public function create(SplFileInfo $file): PatchInterface
    {
        return new $this->class($file);
    }
}
