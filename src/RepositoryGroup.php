<?php

declare(strict_types=1);

namespace WBCUpdater;

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
     * @param string $name
     * @param Repository $repository
     */
    public function addRepository(string $name, Repository $repository): void
    {
        $this->repositories[$name] = $repository;
    }

    /**
     * @param string $name
     *
     * @return Repository|null
     */
    public function getRepository(string $name): ?Repository
    {
        return $this->repositories[$name] ?? null;
    }
}
