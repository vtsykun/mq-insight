<?php

namespace Okvpn\Bundle\MQInsightBundle\Tests\Functional;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class StopConsumerTestExtension extends AbstractExtension
{
    /**
     * @param Context $context
     */
    public function onIdle(Context $context)
    {
        $context->setExecutionInterrupted(true);
        $context->setInterruptedReason('Queue is empty');
    }
}
