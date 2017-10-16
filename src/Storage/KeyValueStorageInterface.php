<?php

namespace Okvpn\Bundle\MQInsightBundle\Storage;

/**
 * Interface provide simple key value storage that allows to read, write, create and delete shared memory segments.
 */
interface KeyValueStorageInterface
{
    /**
     * Get value from memory segments
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key);

    /**
     * Write value to memory segments
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * Delete shared memory segments
     *
     * @param string $key
     */
    public function delete(string $key);
}
