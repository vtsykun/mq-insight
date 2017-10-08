<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Provider;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

/**
 * For amqp-ext. @see http://php.net/manual/fa/book.amqp.php
 */
class AMQPQueueProviderExt implements QueueProviderInterface
{
    /** @var array */
    protected $config;

    /** @var array */
    protected $transportNames = [];

    /** @var DestinationMetaRegistry */
    protected $registry;

    /** @var \AMQPChannel */
    protected $channel;

    public function __construct(DestinationMetaRegistry $registry, array $config = [], array $transportNames = [])
    {
        $this->registry = $registry;
        $this->config = $config;
        $this->transportNames = $transportNames;
    }

    /**
     * {@inheritdoc}
     */
    public function queueCount()
    {
        $this->initialize();

        $count = 0;
        foreach ($this->transportNames as $queueName) {
            $queue =  new \AMQPQueue($this->channel);
            $queue->setName($queueName);
            $queue->setFlags(AMQP_DURABLE);
            $queue->setArgument('x-max-priority', 4);
            $count += $queue->declareQueue();
        }

        return $count;
    }

    protected function initialize()
    {
        if (null === $this->channel) {
            $connection = new \AMQPConnection([
                'host' => $this->config['host'],
                'vhost' => $this->config['vhost'],
                'port' => $this->config['port'],
                'login' => $this->config['user'],
                'password' => $this->config['password']
            ]);

            $connection->connect();
            $this->channel = new \AMQPChannel($connection);

            foreach ($this->registry->getDestinationsMeta() as $destination) {
                $this->transportNames[] = $destination->getTransportName();
            }
        }
    }
}
