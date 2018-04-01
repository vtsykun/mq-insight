<?php

namespace Okvpn\Bundle\MQInsightBundle\Tests\Functional;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ErrorTestMessageProcessor implements MessageProcessorInterface
{
    public static $throwError = true;

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        if (self::$throwError) {
            throw new OkvpnTestException('ErrorTestMessageProcessor exception');
        }

        return self::ACK;
    }
}
