<?php

declare(strict_types=1);

namespace WBCUpdater;

use WBCUpdater\Exceptions\ConfigurationException;

final class RepositoryGroup
{
    private array $repositories;

    /**
     * RepositoryGroup constructor.
     *
     * @param Repository[] $repositories
     */
    public function __construct(array $repositories)
    {
        foreach ($repositories as $name => $repository) {
            $this->addRepository($name, $repository);
        }
    }

    /**
     * @param string|int $name
     * @param Repository $repository
     */
    public function addRepository($name, Repository $repository): void
    {
        if (!is_scalar($name)) {
            throw new ConfigurationException(sprintf('Argument $name isn\'t scalar. Got %s', get_debug_type($name)));
        }

        $this->repositories[$name] = $repository;
    }

    /**
     * @param string|int $name
     *
     * @return Repository|null
     */
    public function getRepository($name): ?Repository
    {
        return $this->repositories[$name] ?? null;
    }
}
