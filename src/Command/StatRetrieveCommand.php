<?php

namespace Okvpn\Bundle\MQInsightBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;
use Okvpn\Bundle\MQInsightBundle\Model\Provider\QueueProviderInterface;
use Okvpn\Bundle\MQInsightBundle\Model\Worker\CallbackTask;
use Okvpn\Bundle\MQInsightBundle\Model\Worker\DelayPool;
use Okvpn\Bundle\MQInsightBundle\Provider\QueuedMessagesProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class StatRetrieveCommand extends ContainerAwareCommand
{
    const DEFAULT_POLLING_TIME = 60; // 1 min

    const NAME = 'okvpn:stat:retrieve';

    /** @var Connection */
    protected $connection;

    /** @var QueueProviderInterface */
    protected $queueProvider;

    /** @var int */
    protected $parentPid;

    /** @var int */
    protected $pollingInterval;

    /** @var QueuedMessagesProvider */
    protected $messagesProvider;

    /** @var DelayPool */
    protected $delayPool;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->addArgument('parentPid', InputArgument::REQUIRED)
            ->addOption('pollingInterval', null, InputOption::VALUE_OPTIONAL, 'The polling interval in sec.')
            ->setDescription('Retrieve message count statistics');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->connection = $this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection();
        $this->queueProvider = $this->getContainer()->get('okvpn_mq_insight.queue_provider');
        $this->parentPid = (int) $input->getArgument('parentPid');
        $this->messagesProvider = $this->getContainer()->get('okvpn_mq_insight.queued_messages_provider');

        $this->delayPool = new DelayPool();

        $this->delayPool->submit(
            new CallbackTask(function () {$this->terminateIfNeeded();}),
            2 * QueuedMessagesProvider::POLLING_TIME,
            'terminateIfNeeded'
        );

        $this->delayPool->submit(
            new CallbackTask(function () {$this->processQueued();}),
            QueuedMessagesProvider::POLLING_TIME,
            'processQueued'
        );

        $this->delayPool->submit(
            new CallbackTask(function () {$this->processCount();}),
            $input->getOption('pollingInterval') ?? self::DEFAULT_POLLING_TIME,
            'processCount'
        );

        $this->delayPool->setLogger(new ConsoleLogger($output));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $shmid = function_exists('sem_get') ? sem_get(AppConfig::getApplicationID()) : null;

        if ($shmid && !sem_acquire($shmid, true)) {
            $output->writeln('<info>Not allowed to run a more one command.</info>');
            return 0;
        }

        $maxCycleNumber = 3600;
        try {
            while ($maxCycleNumber--) {
                $this->delayPool->sync();
                sleep(1);
            }
        } finally {
            if ($shmid) {
                sem_release($shmid);
            }
        }

        return 0;
    }

    protected function processQueued()
    {
        $runningConsumers = ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume');
        $this->messagesProvider->collect($runningConsumers);
    }

    protected function processCount()
    {
        $this->connection->insert(
            'okvpn_mq_state_stat',
            [
                'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                'queue' => $this->queueProvider->queueCount()
            ],
            [
                'created' => Type::DATETIME,
                'queue' => Type::INTEGER
            ]
        );
    }

    protected function terminateIfNeeded()
    {
        $message = '';
        if (ProcessManager::getNumberOfRunningProcess(self::NAME) > 1) {
            $message .= "Running more than one instance StatRetrieveCommand\n";
        }

        if (ProcessManager::getProcessNameByPid($this->parentPid) === '') {
            $message = "The parent process died. Parent pid not found:{$this->parentPid}\n";
        }

        if ($message) {
            throw new \RuntimeException($message);
        }
    }
}
