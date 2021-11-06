<?php

namespace WBCUpdater;

final class Override
{
    private bool $enabled = false;
    /** @var string */
    private string $pattern;
    /** @var string */
    private string $name;

    /**
     * Override constructor.
     *
     * @param string $name
     * @param string $pattern
     */
    public function __construct(string $name, string $pattern)
    {
        $this->pattern = $pattern;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * @return bool
     */
    public function isOn(): bool
    {
        return $this->enabled;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function check(string $name): bool
    {
        return preg_match($this->pattern, $name);
    }
}