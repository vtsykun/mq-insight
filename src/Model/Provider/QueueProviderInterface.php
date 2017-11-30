<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Provider;

interface QueueProviderInterface
{
    /**
     * Should return number of messages in the queue
     *
     * @return int
     */
    public function queueCount();

    /**
     * Return approximate number of messages in the queue for performance
     *
     * @return int
     */
    public function getApproxQueueCount();
}
