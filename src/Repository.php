<?php

declare(strict_types=1);

namespace WBCUpdater;

use WBCUpdater\Downloaders\PatchLinkInterface;
use WBCUpdater\Exceptions\ConfigurationException;
use WBCUpdater\Exceptions\RuntimeException;

final class Repository
{
    /** @var array */
    private array $repository = [];
    /** @var array */
    private array $types = [];
    /** @var string */
    private string $url;

    /**
     * Repository constructor.
     *
     * @param string $url URL for repository (pastebin)
     */
    public function __construct(string $url)
    {
        $this->url = $url;
        $this->loadRepository($url);
    }

    /**
     * @param string $class
     */
    public function register(string $class): void
    {
        if (!is_subclass_of($class, PatchLinkInterface::class)) {
            throw new ConfigurationException(sprintf(
                'Class "%s" does not implement "%s".',
                $class,
                PatchLinkInterface::class
            ));
        }
        $this->types[] = $class;
    }

    /**
     * @param string $name
     *
     * @return PatchLinkInterface
     */
    public function create(string $name): PatchLinkInterface
    {
        if (!isset($this->repository[$name])) {
            throw new RuntimeException("{$name} doesn't exist in repository: {$this->url}.");
        }

        foreach ($this->types as $class) {
            /** @var $class PatchLinkInterface */
            $patchLink = $class::createFromString($this->repository[$name]);
            if ($patchLink->isValid()) {
                return $patchLink;
            }
        }

        throw new RuntimeException("{$name} has invalid url: {$this->repository[$name]}.");
    }

    /**
     * @param string $url
     */
    private function loadRepository(string $url): void
    {
        $repository = (string) file_get_contents($url);
        if (!$repository) {
            throw new RuntimeException("Cannot connect to repository via {$url}.");
        }
        $repository = trim($repository);
        foreach (explode("\n", $repository) as $item) {
            $item = trim($item);
            list($key, $value) = explode('=', $item, 2);
            $this->repository[$key] = $value;
        }
    }
}
