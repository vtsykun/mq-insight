<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Command;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\CronBundle\Command\SynchronousCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronStatRetrieveCommand extends ContainerAwareCommand implements
    CronCommandInterface,
    SynchronousCommandInterface
{
    const COMMAND_NAME = 'oro:cron:okvpn:mq-stat:retrieve';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Run with delay 10 sec. Usually after launching the cron message,
        // the queue contains a few number of messages. A small delay will
        // remove random noise from statistics.
        sleep(10);

        $this->profileQueue();
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
