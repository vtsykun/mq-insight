<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\StatProvider;

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
