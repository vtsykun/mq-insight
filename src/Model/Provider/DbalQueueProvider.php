<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Provider;


use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Okvpn\Bundle\MQInsightBundle\Entity\MQStateStat;

class DbalQueueProvider implements QueueProviderInterface, RandomAccessQueueInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function queueCount()
    {
        $conn = $this->registry->getConnection();
        $result = $conn->executeQuery('SELECT COUNT(1) FROM oro_message_queue')->fetch();

        return $result['count'] ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getApproxQueueCount()
    {
        /** @var Connection $conn */
        $conn = $this->registry->getConnection();
        if ($conn->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $lastValue = $this->registry->getRepository('OkvpnMQInsightBundle:MQStateStat')
                ->getLastValue();

            if ($lastValue instanceof MQStateStat && $lastValue->getQueue() > 1000000) {

                /*
                 * Fast estimated row count.
                 * Usage SELECT COUNT(1) FROM oro_message_queue give a great performance impact
                 *
                 *   Aggregate  (cost=58199.86..58199.87 rows=1 width=0) (actual time=198.660..198.661 rows=1 loops=1)
                 *    ->  Seq Scan on oro_message_queue  (cost=0.00..55748.29 rows=980629 width=0) (actual time=0.030..128.217 rows=996638 loops=1)
                 *  Planning time: 0.074 ms
                 *  Execution time: 198.688 ms
                 */
                $result = $conn
                    ->executeQuery("SELECT n_live_tup FROM pg_stat_all_tables WHERE relname = 'oro_message_queue'")
                    ->fetch();

                return $result['n_live_tup'] ?? $this->queueCount();
            }
        }

        return $this->queueCount();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMessage($messageId)
    {
        if (!$messageId) {
            return false;
        }

        $conn = $this->registry->getConnection();
        $all = $conn
            ->executeQuery(
                'DELETE FROM oro_message_queue WHERE headers LIKE ?',
                ["%\"message_id\":\"$messageId\"%"]
            )
            ->fetchAll();

        return !empty($all);
    }
}
