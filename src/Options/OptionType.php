<?php

declare(strict_types=1);

namespace WBCUpdater\Options;

final class OptionType
{
    /** @var string */
    private string $option;
    /** @var string */
    private string $value;

    public function __construct(string $option, string $value)
    {
        $this->option = $option;
        $this->value = $value;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function is(string $value): bool
    {
        return $this->value === $value;
    }

    /**
     * @param array $types
     *
     * @return bool
     */
    public function any(array $types): bool
    {
        foreach ($types as $type) {
            if ($this->is($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->option;
    }
}
