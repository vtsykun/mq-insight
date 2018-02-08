<?php

namespace Okvpn\Bundle\MQInsightBundle\Tests\Functional;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class WaitTestMessageProcessor implements MessageProcessorInterface
{
    protected $wait;

    /**
     * @param string $wait
     */
    public function __construct($wait)
    {
        $this->wait;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = @json_decode($message->getBody(), true);
        $wait = is_array($body) && isset($body['wait']) ? $body['wait'] : $this->wait;
        usleep(1000 * $wait);

        return self::ACK;
    }
}
