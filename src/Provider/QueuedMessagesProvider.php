<?php

namespace Okvpn\Bundle\MQInsightBundle\Provider;

use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;
use Okvpn\Bundle\MQInsightBundle\Storage\KeyValueStorageInterface;

class QueuedMessagesProvider
{
    const KEEP_RESULT_TIME = 300; //5 min
    const POLLING_TIME = 5; // 5 sec

    protected $storage;

    /**
     * @param KeyValueStorageInterface $storage
     */
    public function __construct(KeyValueStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param array $pids
     */
    public function collect(array $pids)
    {
        $applicationId = $this->getApplicationId();
        $previousResult = $this->storage->get($applicationId);
        if (!isset($previousResult[0]) || !isset($previousResult[0][1])) {
            $previousResult = [];
        }

        $currentTime = time();
        $currentItem = 0;
        foreach ($pids as $pid) {
            $result = $this->storage->get((string)$pid);
            $currentItem += (is_array($result) && $currentTime - $result[1] < 3 * self::POLLING_TIME) ? $result[0] : 0;
            if (is_array($result) && $currentTime - $result[1] > self::KEEP_RESULT_TIME) {
                $this->flush($pid);
            }
        }

        $previousResult[] = [$currentTime, $currentItem];
        $result = [];
        foreach ($previousResult as $item) {
            if ($currentTime - $item[0] < 2 * self::KEEP_RESULT_TIME) {
                $result[] = $item;
            }
        }

        $this->storage->set($applicationId, array_values($result));
    }

    /**
     * @param array $pids
     * @return array
     */
    public function filterNotActivePids(array $pids)
    {
        $currentTime = time();
        $runningPids = [];
        foreach ($pids as $pid) {
            $result = $this->storage->get((string) $pid);
            if (is_array($result) && $currentTime - $result[1] < 2 * self::KEEP_RESULT_TIME) {
                $runningPids[] = $pid;
            }
        }

        return $runningPids;
    }

    /**
     * @param string $pid
     * @param mixed $value
     */
    public function saveResultForPid($pid, $value)
    {
        $this->storage->set((string) $pid, $value);
    }

    /**
     * @param string $pid
     * @return bool
     */
    public function flush($pid)
    {
        try {
            return $this->storage->delete((string) $pid);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array|mixed
     */
    public function getQueuedMessages()
    {
        $applicationId = $this->getApplicationId();
        $previousResult = $this->storage->get($applicationId);
        if (!isset($previousResult[0]) || !isset($previousResult[0][1])) {
            $previousResult = [];
        }

        return $previousResult;
    }

    /**
     * @return int
     */
    protected function getApplicationId()
    {
        return AppConfig::getApplicationID();
    }
}
