<?php

namespace Okvpn\Bundle\MQInsightBundle\Client;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class DebugMessageProducer implements MessageProducerInterface, DebugProducerInterface
{
    /** @var MessageProducerInterface */
    protected $messageProducer;

    /** @var int */
    protected static $count = 0;

    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        $this->messageProducer = $messageProducer;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message)
    {
        $this->messageProducer->send($topic, $message);
        self::$count++;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        return self::$count;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        self::$count = 0;
    }
}
