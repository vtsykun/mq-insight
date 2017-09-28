<?php

namespace Okvpn\Bundle\MQInsightBundle\Client;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class DebugMessageProducer implements MessageProducerInterface, DebugProducerInterface
{
    /** @var MessageProducerInterface */
    protected $messageProducer;

    /** @var int */
    protected $count = 0;

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
        $this->count++;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->count = 0;
    }
}
