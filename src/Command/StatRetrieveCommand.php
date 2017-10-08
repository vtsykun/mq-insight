<?php

namespace Okvpn\Bundle\MQInsightBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;
use Okvpn\Bundle\MQInsightBundle\Model\Provider\QueueProviderInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StatRetrieveCommand extends ContainerAwareCommand
{
    const DEFAULT_POLLING_TIME = 30; // 30 sec

    const NAME = 'okvpn:stat:retrieve';

    /** @var Connection */
    protected $connection;

    /** @var QueueProviderInterface */
    protected $queueProvider;

    /** @var int */
    protected $parentPid;

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

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pollingInterval = $input->getOption('pollingInterval') ?? self::DEFAULT_POLLING_TIME;
        $shmid = function_exists('sem_get') ? sem_get(AppConfig::getApplicationID()) : null;

        if ($shmid && !sem_acquire($shmid, true)) {
            $output->writeln('<info>Not allowed to run a more one command.</info>');
            return 0;
        }

        $maxCycleNumber = 1000;
        try {
            while ($maxCycleNumber--) {
                // terminate if needed
                if ($this->shouldBeTerminate()) {
                    $output->writeln('<info>Not allowed to run a more one command.</info>');
                    return 0;
                }

                $this->process();
                sleep($pollingInterval);
            }
        } finally {
            if ($shmid) {
                sem_release($shmid);
            }
        }

        return 0;
    }

    protected function process()
    {
        $count = $this->queueProvider->queueCount();

        $this->connection->insert(
            'okvpn_mq_state_stat',
            [
                'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                'queue' => $count
            ],
            [
                'created' => Type::DATETIME,
                'queue' => Type::INTEGER
            ]
        );
    }

    /**
     * @return bool
     */
    protected function shouldBeTerminate()
    {
        return ProcessManager::getNumberOfRunningProcess(self::NAME) > 1
            || ProcessManager::getProcessNameByPid($this->parentPid) === '';
    }
}
