<?php

namespace Okvpn\Bundle\MQInsightBundle\Provider;

use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;
use Okvpn\Bundle\MQInsightBundle\Storage\KeyValueStorageInterface;

class QueuedMessagesProvider
{
    const KEEP_RESULT_TIME = 300; //5 min
    const POLLING_TIME = 5; // 5 sec

    protected $storage;


    public function __construct(KeyValueStorageInterface $storage)
    {
        $this->storage = $storage;
    }

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
            $currentItem += (is_array($result) && $currentTime - $result[1] < 3*self::POLLING_TIME) ? $result[0] : 0;
        }

        $previousResult[] = [$currentTime, $currentItem];
        $result = [];
        foreach ($previousResult as $item) {
            if ($currentTime - $item[0] < self::KEEP_RESULT_TIME) {
                $result[] = $item;
            }
        }

        $this->storage->set($applicationId, array_values($result));
    }

    public function saveResultForPid($pid, $value)
    {
        $this->storage->set((string) $pid, $value);
    }

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
