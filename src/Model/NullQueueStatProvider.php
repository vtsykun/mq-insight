<?php

namespace Okvpn\Bundle\MQInsightBundle\Model;

class NullQueueStatProvider implements QueueStatProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function queueCount()
    {
        return 0;
    }
}
