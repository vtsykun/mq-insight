<?php

namespace Okvpn\Bundle\MQInsightBundle\Extension;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Okvpn\Bundle\MQInsightBundle\Client\DebugProducerInterface;
use Okvpn\Bundle\MQInsightBundle\Command\StatRetrieveCommand;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;
use Okvpn\Bundle\MQInsightBundle\Model\Counter;
use Okvpn\Bundle\MQInsightBundle\Model\Worker\CallbackTask;
use Okvpn\Bundle\MQInsightBundle\Model\Worker\DelayPool;
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

    /** @var Process */
    protected $process;

    /** @var DelayPool */
    protected $delayPool;

    /** @var Counter */
    protected $counter;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->delayPool = new DelayPool();
        $this->counter = new Counter();
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        $this->delayPool->submit(
            new CallbackTask(function () {$this->publishSpeedStat();}),
            QueuedMessagesProvider::POLLING_TIME - 1
        );

        $this->delayPool->submit(
            new CallbackTask(function () {$this->publishCountStat();}),
            static::POLLING_INTERVAL
        );

        $this->delayPool->submit(
            new CallbackTask(function () {$this->runStatRetrieveCommandIfNeeded();}),
            2 * QueuedMessagesProvider::POLLING_TIME
        );

        $this->delayPool->submit(
            new CallbackTask(function () {$this->publishProcessorStats();}),
            static::POLLING_INTERVAL
        );

        $this->counter->add('queued');
        $this->counter->add('processed');
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        if (null !== $this->start) {
            $this->processRecord(
                'system',
                MessageProcessorInterface::ACK,
                microtime(true) - $this->start
            );
        }

        $this->start = microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $message = $context->getMessage();
        if (null !== $message) {
            $this->processRecord(
                $message->getProperty(Config::PARAMETER_PROCESSOR_NAME),
                $context->getStatus(),
                microtime(true) - $this->start
            );
        }

        $this->start = microtime(true);
        $this->counter->tickAll();

        $this->sync();
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        if (null !== $this->start) {
            $this->processRecord(
                'idle',
                MessageProcessorInterface::ACK,
                microtime(true) - $this->start
            );
        }

        $this->sync();
        $this->start = microtime(true);
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

            $provider = $this->container->get('okvpn_mq_insight.queued_messages_provider');
            $provider->flush(getmypid());
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

    protected function sync()
    {
        $this->delayPool->sync();
    }

    protected function publishSpeedStat()
    {
        $microtime = microtime(true);
        $queued = $this->counter->reset('queued');
        static $lastSyncTime = 0;

        $speed = ($lastSyncTime !== 0) ? $queued/($microtime - $lastSyncTime) : 0;
        $provider = $this->container->get('okvpn_mq_insight.queued_messages_provider');
        $provider->saveResultForPid(getmypid(), [round($speed, 1), time()]);
        $lastSyncTime = $microtime;
    }

    /**
     * @param string $name
     * @param string $status
     * @param float $long
     */
    protected function processRecord($name, $status, $long)
    {
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
        switch ($status) {
            case MessageProcessorInterface::ACK:
                $this->stats[$name]['ack']++;
                break;
            case MessageProcessorInterface::REJECT:
                $this->stats[$name]['reject']++;
                break;
            case MessageProcessorInterface::REQUEUE:
                $this->stats[$name]['requeue']++;
                break;
            default:
                $this->stats[$name]['ack']++;
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
                'removed' => $this->counter->reset('processed'),
                'channel' => 'consumer'
            ],
            [
                'created' => Type::DATETIME,
                'added' => Type::INTEGER,
                'removed' => Type::INTEGER,
                'channel' => Type::STRING,
            ]
        );

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
            && !ProcessManager::isProcessRunning(sprintf("'%s'", StatRetrieveCommand::NAME . ' ' . AppConfig::getApplicationID()))
        ) {
            $env = $this->container->get('kernel')->getEnvironment();
            $pb = new ProcessBuilder();

            $phpFinder = new PhpExecutableFinder();
            $phpPath   = $phpFinder->find();
            $pb
                ->add($phpPath)
                ->add($_SERVER['argv'][0])
                ->add(StatRetrieveCommand::NAME)
                ->add(AppConfig::getApplicationID())
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
