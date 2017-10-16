<?php

namespace Okvpn\Bundle\MQInsightBundle\Extension;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Okvpn\Bundle\MQInsightBundle\Client\DebugProducerInterface;
use Okvpn\Bundle\MQInsightBundle\Command\StatRetrieveCommand;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Okvpn\Bundle\MQInsightBundle\Provider\QueuedMessagesProvider;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class MQStatExtension extends AbstractExtension
{
    const POLLING_INTERVAL = 120; // 2 min;

    /** @var array */
    protected $stats = [];

    /** @var float */
    protected $start;

    /** @var ContainerInterface */
    protected $container;

    /** @var int */
    protected $processedCount = 0;

    /** @var Process */
    protected $process;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        $this->sync(true);
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
            $this->publishCountStat();
            if ($context->isExecutionInterrupted() && $context->getException()) {
                $message = $context->getMessage();
                $name = $message ? $message->getProperty(Config::PARAMETER_PROCESSOR_NAME) : null;
                $uid = $message ? $message->getMessageId() : null;
                $this->publishErrorStat($context->getException(), $name, $uid);
            }
        } catch (\Exception $e) {
            // do nothing
        } finally {
            try {
                if ($this->process) {
                    $this->process->stop(2, SIGKILL);
                }
            } catch (\Exception $e) {}
        }
    }

    protected function sync($isOnPostReceived = false)
    {
        $this->syncQueued($isOnPostReceived);
        $this->syncStatistics($isOnPostReceived);
    }

    protected function syncStatistics($isOnPostReceived)
    {
        static $lastSyncProcessorStat = 0;
        if (time() - $lastSyncProcessorStat > self::POLLING_INTERVAL) {
            $this->runStatRetrieveCommandIfNeeded();
            $this->publishCountStat();
            $this->publishProcessorStats();
            $lastSyncProcessorStat = time();
        }
    }

    protected function syncQueued($isOnPostReceived)
    {
        $microtime = microtime(true);
        static $queued = 0;
        static $lastSyncTime = 0;

        if ($isOnPostReceived) {
            $queued++;
        }

        if ($microtime - $lastSyncTime > (QueuedMessagesProvider::POLLING_TIME - 1)) {
            if ($lastSyncTime === 0) {
                $lastSyncTime = $microtime;
                $queued = 0;
            }

            $speed = ($queued > 0) ? $queued/($microtime - $lastSyncTime) : 0;

            $provider = $this->container->get('okvpn_mq_insight.queued_messages_provider');
            $provider->saveResultForPid(getmypid(), [round($speed, 1), time()]);
            $lastSyncTime = $microtime;
            $queued = 0;
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
        }

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

    protected function publishProcessorStats()
    {
        $conn = $this->getEntityManager()->getConnection();

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
        $conn = $this->getEntityManager()->getConnection();

        $conn->insert(
            'okvpn_mq_change_stat',
            [
                'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                'added' => $this->getDebugProducer()->getCount(),
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
        $this->getDebugProducer()->clear();
    }

    protected function publishErrorStat(\Exception $e, $processorName = null, $messageId = null)
    {
        $conn = $this->getEntityManager()->getConnection();
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

    protected function runStatRetrieveCommandIfNeeded()
    {
        if (!getenv('SKIP_STAT_RETRIEVE')
            && !$this->container->getParameter('okvpn_mq_insight.skip_stat_retrieve')
            && !ProcessManager::isProcessRunning(StatRetrieveCommand::NAME)
        ) {
            $env = $this->container->get('kernel')->getEnvironment();
            $pb = new ProcessBuilder();

            $phpFinder = new PhpExecutableFinder();
            $phpPath   = $phpFinder->find();
            $pb
                ->add($phpPath)
                ->add($_SERVER['argv'][0])
                ->add(StatRetrieveCommand::NAME)
                ->add(getmypid())
                ->add("--env=$env");

            $process = $pb
                ->setTimeout(3600)
                ->inheritEnvironmentVariables(true)
                ->getProcess();

            $process->start();
            $this->process = $process;
        }
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine.orm.default_entity_manager');
    }

    /**
     * @return DebugProducerInterface
     */
    protected function getDebugProducer()
    {
        return $this->container->get('okvpn_mq_insight.debug_message_producer');
    }
}
