<?php

namespace Okvpn\Bundle\MQInsightBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupOldStatisticsCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '5 0 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:cron:mq-insight:cleanup');
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->registry = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->registry->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
    }

    protected function processCountStatistics()
    {
    }
}
