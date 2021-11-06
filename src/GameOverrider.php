<?php

declare(strict_types=1);

namespace WBCUpdater;

use InvalidArgumentException;

final class GameOverrider
{
    /** @var Override[] */
    private array $overrides = [];

    /**
     * @param Override $override
     */
    public function add(Override $override)
    {
        if (!isset($this->overrides[$override->getName()])) {
            $this->overrides[$override->getName()] = $override;
        } else {
            throw new InvalidArgumentException("Overrider {$override->getName()} already exists.");
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function canOverride(string $name): bool
    {
        foreach ($this->overrides as $override) {
            if ($override->check($name)) {
                return $override->isOn();
            }
        }

        return true;
    }
}
