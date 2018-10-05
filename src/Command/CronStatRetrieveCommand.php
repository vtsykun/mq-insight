<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Command;

use Doctrine\DBAL\Types\Type;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Okvpn\Bundle\MQInsightBundle\Model\Worker\CallbackTask;
use Okvpn\Bundle\MQInsightBundle\Model\Worker\DelayPool;
use Okvpn\Bundle\MQInsightBundle\Provider\QueuedMessagesProvider;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CronBundle\Command\SynchronousCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

class CronStatRetrieveCommand extends ContainerAwareCommand implements
    CronCommandInterface,
    SynchronousCommandInterface
{
    public const COMMAND_NAME = 'oro:cron:okvpn:mq-stat:retrieve';
    protected const DEFAULT_POLLING_TIME = 60; // 1 min

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->addOption('lifetime', null, InputOption::VALUE_OPTIONAL, 'Maximum lifetime of the process in sec.', 60)
            ->addOption('pollingInterval', null, InputOption::VALUE_OPTIONAL, 'The polling interval in sec.', static::DEFAULT_POLLING_TIME)
            ->setDescription('Retrieve message count statistics');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // For BC with Symfony 2.8
        $lock = new LockHandler('okvpn_mq_stat');
        if (!$lock->lock()) {
            $output->writeln('Aborting, another of the same command is still active');
            return;
        }

        // Run with delay 5 sec. Usually after launching the cron message,
        // the queue contains a few number of messages. A small delay will
        // remove random noise from statistics.
        sleep(5);

        try {
            $lifetime = $input->getOption('lifetime') - 2 * QueuedMessagesProvider::POLLING_TIME;
            $startTime = time();
            $delayPool = new DelayPool();
            $delayPool->setLogger(new ConsoleLogger($output));
            $delayPool->submit(
                new CallbackTask(function () {$this->processQueued();}),
                QueuedMessagesProvider::POLLING_TIME,
                'processQueued'
            );

            $delayPool->submit(
                new CallbackTask(function () {$this->profileQueue();}),
                $input->getOption('pollingInterval') ?? static::DEFAULT_POLLING_TIME,
                'processCount'
            );

            while (time() - $startTime < $lifetime) {
                $delayPool->sync();
                sleep(1);
            }
        } finally {
            $lock->release();
        }
    }

    protected function profileQueue()
    {
        $connection = $this->getConnection();
        $connection->insert(
            'okvpn_mq_state_stat',
            [
                'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                'queue' => $this->getQueueProvider()->queueCount()
            ],
            [
                'created' => Type::DATETIME,
                'queue' => Type::INTEGER
            ]
        );
    }

    protected function processQueued()
    {
        $runningConsumers = ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume');
        $messagesProvider = $this->getContainer()->get('okvpn_mq_insight.queued_messages_provider');
        $messagesProvider->collect($runningConsumers);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnection()
    {
        return $this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection();
    }

    /**
     * @return \Okvpn\Bundle\MQInsightBundle\Model\Provider\DefaultQueueProvider
     */
    protected function getQueueProvider()
    {
        return $this->getContainer()->get('okvpn_mq_insight.queue_provider');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return !$this->getContainer()->getParameter('okvpn_mq_insight.skip_stat_retrieve');
    }
}
