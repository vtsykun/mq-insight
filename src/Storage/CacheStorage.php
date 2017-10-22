<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Storage;

use Doctrine\Common\Cache\CacheProvider;

class CacheStorage implements KeyValueStorageInterface
{
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $value)
    {
        $this->cacheProvider->save($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        return $this->cacheProvider->fetch($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key)
    {
        return $this->cacheProvider->delete($key);
    }
}
