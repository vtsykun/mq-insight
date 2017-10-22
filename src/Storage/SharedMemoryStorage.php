<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Storage;

use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;

class SharedMemoryStorage implements KeyValueStorageInterface
{
    /**
     * @var int
     */
    protected $applicationKey;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @param int $applicationKey
     */
    public function __construct(int $applicationKey = null)
    {
        $this->applicationKey = $applicationKey !== null ?: AppConfig::getApplicationID();
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value)
    {
        $this->init();
        $key = $this->getKey($key);

        return shm_put_var($this->resource, $key, serialize($value));
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        $this->init();
        $key = $this->getKey($key);

        try {
            $value = shm_get_var($this->resource, $key);

            return unserialize($value);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key)
    {
        $this->init();
        $key = $this->getKey($key);

        return shm_remove_var($this->resource, $key);
    }

    protected function getKey(string $key): int
    {
        $key = hexdec(substr(md5($key), 0, 7));
        return (int) $key;
    }

    protected function init()
    {
        if ($this->resource === null) {
            $this->resource = shm_attach($this->applicationKey, 2048 * 1024, 0777);
        }
    }
}
