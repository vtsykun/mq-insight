<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Provider;

use Doctrine\ORM\EntityManagerInterface;

class DbalQueueProvider implements QueueProviderInterface, RandomAccessQueueInterface
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

    /**
     * {@inheritdoc}
     */
    public function deleteMessage($messageId)
    {
        if (!$messageId) {
            return false;
        }

        $conn = $this->entityManager->getConnection();
        $all = $conn
            ->executeQuery(
                'DELETE FROM oro_message_queue WHERE headers LIKE ?',
                ["%\"message_id\":\"$messageId\"%"]
            )
            ->fetchAll();

        return !empty($all);
    }
}
