<?php

namespace Okvpn\Bundle\MQInsightBundle\Model;

use Doctrine\ORM\EntityManagerInterface;

class DbalQueueStatProvider implements QueueStatProviderInterface
{
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function queueCount()
    {
        $conn = $this->entityManager->getConnection();
        $result = $conn->executeQuery('SELECT COUNT(1) FROM oro_message_queue')->fetch();

        return $result['count'] ?? 0;
    }
}
