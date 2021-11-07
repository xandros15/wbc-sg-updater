<?php

declare(strict_types=1);

namespace WBCUpdater;

use ArrayAccess;
use Symfony\Component\Yaml\Yaml;
use WBCUpdater\Exceptions\BadMethodCallException;
use WBCUpdater\Exceptions\SystemException;

final class Config implements ArrayAccess
{
    private string $filename;
    private array $params;
    private bool $isDirty = false;

    /**
     * Config constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->params = Yaml::parseFile($filename);
    }

    /**
     * @throws SystemException
     */
    public function save(): void
    {
        if ($this->isDirty && file_put_contents($this->filename, Yaml::dump($this->params, 2, 2)) === false) {
            throw new SystemException("Cannot save config file in {$this->filename}.");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->params[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->params[$offset] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        if (isset($this->params[$offset]) && $this->params[$offset] !== $value) {
            $this->params[$offset] = $value;
            $this->isDirty = true;
        }
    }

    /**
     * That method can't be used on Config class object
     *
     * @param $offset
     *
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('You cannot unset any of param.');
    }
}
