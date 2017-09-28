<?php

namespace Okvpn\Bundle\MQInsightBundle\Client;

/**
 * For getting info about the number messages that was processed by a producer
 */
interface DebugProducerInterface
{
    /**
     * Clear statistics
     */
    public function clear();

    /**
     * Get count of message
     *
     * @return int
     */
    public function getCount();
}
