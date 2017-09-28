<?php

namespace Okvpn\Bundle\MQInsightBundle\Model;

interface QueueStatProviderInterface
{
    /**
     * Should return number of messages in the queue
     *
     * @return int
     */
    public function queueCount();
}
