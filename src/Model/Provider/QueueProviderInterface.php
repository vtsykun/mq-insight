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
}
