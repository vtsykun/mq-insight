<?php

namespace Okvpn\Bundle\MQInsightBundle\Extension;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Okvpn\Bundle\MQInsightBundle\Client\DebugProducerInterface;
use Okvpn\Bundle\MQInsightBundle\Model\QueueStatProviderInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class MQStatExtension extends AbstractExtension
{
    const PROCESSOR_STAT_SYNC = 15; // 1 min;

    const COUNT_STAT_SYNC = 15; // 30 sec;

    /** @var array */
    protected $stats = [];

    /** @var float */
    protected $start;

    /** @var int */
    protected $lastSyncProcessorStat;

    /** @var int */
    protected $lastSyncCountStat;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var DebugProducerInterface */
    protected $debugProducer;

    /** @var QueueStatProviderInterface */
    protected $statProvider;

    /** @var int */
    protected $processedCount = 0;

    public function __construct(
        EntityManagerInterface $entityManager,
        DebugProducerInterface $debugProducer,
        QueueStatProviderInterface $statProvider
    ) {
        $this->entityManager = $entityManager;
        $this->debugProducer = $debugProducer;
        $this->statProvider = $statProvider;

        $this->lastSyncCountStat = time();
        $this->lastSyncProcessorStat = time();
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $this->start = microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $long = microtime(true) - $this->start;
        $this->processRecord($context, $long);
        $this->processedCount++;

        $this->sync();
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $this->sync();
    }

    /**
     * @param Context $context
     */
    public function onInterrupted(Context $context)
    {
        try {
            $this->publishProcessorStats();
            if ($context->isExecutionInterrupted() && $context->getException()) {
                $message = $context->getMessage();
                $name = $message ? $message->getProperty(Config::PARAMETER_PROCESSOR_NAME) : null;
                $uid = $message ? $message->getMessageId() : null;
                $this->publishErrorStat($context->getException(), $name, $uid);
            }
        } catch (\Exception $e) {
            // do nothing
        }
    }

    protected function sync()
    {
        $time = time();
        if ($time - $this->lastSyncCountStat > self::COUNT_STAT_SYNC) {
            $this->publishCountStat();
            $this->lastSyncCountStat = time();
        }

        if ($time - $this->lastSyncProcessorStat > self::PROCESSOR_STAT_SYNC) {
            $this->publishProcessorStats();
            $this->lastSyncProcessorStat = time();
        }
    }

    /**
     * @param Context $context
     * @param float $long
     */
    protected function processRecord(Context $context, $long)
    {
        $message = $context->getMessage();
        if (null === $message) {
            return;
        }

        $name = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (!array_key_exists($name, $this->stats)) {
            $this->stats[$name] = [
                'ack' => 0,
                'reject' => 0,
                'requeue' => 0,
                'avg_time' => $long,
                'min_time' => $long,
                'max_time' => $long,
            ];
        } else {
            $total = $this->stats[$name]['ack'] + $this->stats[$name]['requeue'] + $this->stats[$name]['reject'];
            switch ($context->getStatus()) {
                case MessageProcessorInterface::ACK:
                    $this->stats[$name]['ack']++;
                    break;
                case MessageProcessorInterface::REJECT:
                    $this->stats[$name]['reject']++;
                    break;
                case MessageProcessorInterface::REQUEUE:
                    $this->stats[$name]['requeue']++;
                    break;
            }

            $this->stats[$name]['min_time'] = min($this->stats[$name]['min_time'], $long) ?? $long;
            $this->stats[$name]['max_time'] = max($this->stats[$name]['max_time'], $long) ?? $long;
            $this->stats[$name]['avg_time'] = ($this->stats[$name]['avg_time'] * $total + $long)/($total + 1);
        }
    }

    protected function publishProcessorStats()
    {
        $conn = $this->entityManager->getConnection();

        foreach ($this->stats as $name => $stat) {
            $stat['name'] = $name;
            $stat['created'] = new \DateTime('now', new \DateTimeZone('UTC'));
            $conn->insert('okvpn_mq_processor_stat', $stat , [
                'name' => Type::STRING,
                'created' => Type::DATETIME,
                'avg_time' => Type::DECIMAL,
                'max_time' => Type::DECIMAL,
                'min_time' => Type::DECIMAL,
                'ack' => Type::INTEGER,
                'reject' => Type::INTEGER,
                'requeue' => Type::INTEGER,
            ]);
        }

        $this->stats = [];
    }

    protected function publishCountStat()
    {
        $conn = $this->entityManager->getConnection();

        $conn->insert(
            'okvpn_mq_change_stat',
            [
                'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                'added' => $this->debugProducer->getCount(),
                'removed' => $this->processedCount,
                'channel' => 'consumer'
            ],
            [
                'created' => Type::DATETIME,
                'added' => Type::INTEGER,
                'removed' => Type::INTEGER,
                'channel' => Type::STRING,
            ]
        );

        $this->processedCount = 0;
        $this->debugProducer->clear();
    }

    protected function publishErrorStat(\Exception $e, $processorName = null, $messageId = null)
    {
        $conn = $this->entityManager->getConnection();
        $log = sprintf(
            "[%s] %s in %s:%s.\nTrace:\n%s",
            get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()
        );

        $conn->insert(
            'okvpn_mq_error_stat',
            [
                'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                'processor_name' => $processorName,
                'message_id' => $messageId,
                'log' => $log,
            ],
            [
                'created' => Type::DATETIME,
                'processor_name' => Type::STRING,
                'message_id' => Type::STRING,
                'log' => Type::TEXT,
            ]
        );
    }
}
