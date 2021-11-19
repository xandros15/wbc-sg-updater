<?php

declare(strict_types=1);

namespace WBCUpdater\Downloaders;

use SplFileInfo;
use WBCUpdater\Archives\PatchInterface;
use WBCUpdater\Exceptions\ConfigurationException;
use WBCUpdater\Exceptions\SystemException;

final class PatchFactory
{
    /** @var array */
    private array $classes = [];

    /**
     * PatchFactory constructor.
     *
     * @param array $classes
     */
    public function __construct(array $classes = [])
    {
        foreach ($classes as $class) {
            $this->register($class);
        }
    }

    /**
     * @param string $class
     */
    public function register(string $class)
    {
        if (!is_subclass_of($class, PatchInterface::class)) {
            throw new ConfigurationException(sprintf(
                'Class "%s" does not implement "%s".',
                $class,
                PatchInterface::class
            ));
        }

        $this->classes[] = $class;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return PatchInterface
     * @throws SystemException
     */
    public function create(SplFileInfo $file): PatchInterface
    {
        foreach ($this->classes as $class) {
            /** @var $class PatchInterface */
            if ($class::isCorrectExtension($file->getExtension())) {
                return new $class($file);
            }
        }

        throw new SystemException('Extension not supported.');
    }
}
