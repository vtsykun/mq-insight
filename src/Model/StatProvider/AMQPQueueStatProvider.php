<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\StatProvider;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * For PhpAmqpLib
 */
class AMQPQueueStatProvider implements QueueStatProviderInterface, AbstractConnectionAwareInterface
{
    /** @var ConnectionInterface */
    protected $connection;

    /** @var AbstractConnection */
    protected $amqpConnection;

    /** @var array */
    protected $transportNames;

    /** @var AMQPChannel */
    protected $channel;

    /** @var DestinationMetaRegistry */
    protected $registry;

    /**
     * @param DestinationMetaRegistry $registry
     * @param AbstractConnection|null $connection
     * @param array $transportNames
     */
    public function __construct(DestinationMetaRegistry $registry, AbstractConnection $connection = null, array $transportNames = [])
    {
        $this->amqpConnection = $connection;
        $this->registry = $registry;
        $this->transportNames = $transportNames;
    }

    /**
     * {@inheritdoc}
     */
    public function queueCount()
    {
        $count = 0;
        if (false === $this->initialize()) {
            return $count;
        }

        foreach ($this->transportNames as $queueName) {
            $queue = $this->getQueue($queueName);
            $count += $this->channel->queue_declare(...$queue)[1];
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return bool
     */
    protected function initialize()
    {
        if (null === $this->channel && null !== $this->connection) {
            foreach ($this->registry->getDestinationsMeta() as $destination) {
                $this->transportNames[] = $destination->getTransportName();
            }

            $reflect = new \ReflectionObject($this->connection);
            if (!$reflect->hasProperty('connection')) {
                $this->amqpConnection = false;
                return false;
            }

            $prop = $reflect->getProperty('connection');
            $prop->setAccessible(true);
            $this->amqpConnection = $prop->getValue($this->connection);
            $this->channel = $this->amqpConnection ? $this->amqpConnection->channel() : false;
        }

        return $this->amqpConnection instanceof AbstractConnection;
    }

    /**
     * @param string $queueName
     * @return array
     */
    protected function getQueue($queueName)
    {
        return [$queueName, false, true, false, false, false, new AMQPTable(['x-max-priority' => 4])];
    }
}
