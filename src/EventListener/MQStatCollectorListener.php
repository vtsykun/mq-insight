<?php

namespace Okvpn\Bundle\MQInsightBundle\EventListener;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Okvpn\Bundle\MQInsightBundle\Client\DebugProducerInterface;

class MQStatCollectorListener
{
    private $debugProducer;
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param DebugProducerInterface $debugProducer
     */
    public function __construct(EntityManagerInterface $entityManager, DebugProducerInterface $debugProducer)
    {
        $this->entityManager = $entityManager;
        $this->debugProducer = $debugProducer;
    }

    public function onKernelTerminate()
    {
        $this->flush('kernel');
    }

    public function onConsoleTerminate()
    {
        $this->flush('console');
    }

    private function flush($channel)
    {
        if (!$this->entityManager->isOpen() || $this->debugProducer->getCount() === 0) {
            return;
        }

        $conn = $this->entityManager->getConnection();
        try {
            $conn->insert(
                'okvpn_mq_change_stat',
                [
                    'created' => new \DateTime('now', new \DateTimeZone('UTC')),
                    'added' => $this->debugProducer->getCount(),
                    'removed' => 0,
                    'channel' => $channel
                ],
                [
                    'created' => Type::DATETIME,
                    'added' => Type::INTEGER,
                    'removed' => Type::INTEGER,
                    'channel' => Type::STRING,
                ]
            );
        } catch (\Exception $e) {
            // do nothing
            // for example connection is close
        }

        $this->debugProducer->clear();
    }
}
