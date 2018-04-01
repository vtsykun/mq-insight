<?php

namespace Okvpn\Bundle\MQInsightBundle\Tests\Functional;

use Okvpn\Bundle\MQInsightBundle\Entity\MQErrorStat;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Log\NullLogger;

class MQStatCollectErrorTest extends WebTestCase
{
    /** @var QueueConsumer */
    protected $consumer;

    /** @var ChainExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        putenv('SKIP_STAT_RETRIEVE=true');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $container = self::getContainer();

        if ($container->hasParameter('message_queue_transport') &&
            $container->getParameter('message_queue_transport') === 'null'
        ) {
            $this->markTestSkipped('The null message queue transport is not allow for tests');
        }

        $consumer = $container->get('oro_message_queue.client.queue_consumer');
        $registry = $container->get('oro_message_queue.client.meta.destination_meta_registry');
        foreach ($registry->getDestinationsMeta() as $destinationMeta) {
            $consumer->bind(
                $destinationMeta->getTransportName(),
                $container->get('oro_message_queue.client.delegate_message_processor')
            );
        }

        $conn = $this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection();
        $conn->executeQuery('delete from oro_message_queue');
        $conn->executeQuery('delete from oro_message_queue_job_unique');
        $conn->executeQuery('delete from oro_message_queue_job');

        $this->consumer = $consumer;
    }

    public function testCollectError()
    {
        $conn = $this->getContainer()->get('doctrine.orm.default_entity_manager')->getConnection();

        $stopConsumerCallable = function ($event, Context $context) use ($conn) {
            static $counter = 0;
            switch ($event) {
                case 'onInterrupted':
                    $counter++;
                    if ($counter > 2) {
                        ErrorTestMessageProcessor::$throwError = false;
                    }
                    return;
                case 'onIdle':
                    $row = $conn->executeQuery('select count(1) as cnt from oro_message_queue')->fetch();
                    if ($row && $row['cnt'] == 0) {
                        $context->setExecutionInterrupted(true);
                        $context->setInterruptedReason('Queue is empty');
                    }

                    $conn->executeQuery('update oro_message_queue set delayed_until = null');
                    return;
            }
        };

        $producer = $this->getContainer()->get('oro_message_queue.client.message_producer');
        $producer->send('topic_okvpn.error.topic1', 'empty');

        $extensions[] = new LoggerExtension(new NullLogger());
        $extensions[] = new TestConsumerExtension($stopConsumerCallable);

        $extension = new ChainExtension($extensions);

        while (true) {
            try {
                $this->consumer->consume($extension);
            } catch (OkvpnTestException $exception) {
                // skip
            }

            $row = $conn->executeQuery('select count(1) as cnt from oro_message_queue')->fetch();
            if ($row && $row['cnt'] == 0) {
                break;
            }
        }

        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $qb = $manager->createQueryBuilder();
        $error = $qb->select('e')
            ->from(MQErrorStat::class, 'e')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        $this->assertNotEmpty($error);
        $this->assertEquals(2, $error->getRedeliverCount());
    }
}
