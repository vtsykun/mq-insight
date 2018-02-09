<?php

namespace Okvpn\Bundle\MQInsightBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Extension\LoggerExtension;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Client\Config;
use Psr\Log\NullLogger;

/**
 * @dbIsolationPerTest
 */
class MQStatExtensionTest extends WebTestCase
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
        $this->initClient();
        $container = self::getContainer();

        $consumer = $container->get('oro_message_queue.client.queue_consumer');
        $registry = $container->get('oro_message_queue.client.meta.destination_meta_registry');
        foreach ($registry->getDestinationsMeta() as $destinationMeta) {
            $consumer->bind(
                $destinationMeta->getTransportName(),
                $container->get('oro_message_queue.client.delegate_message_processor')
            );
        }

        $extensions[] = new LoggerExtension(new NullLogger());
        $extensions[] = new StopConsumerTestExtension();

        $this->consumer = $consumer;
        $this->extension = new ChainExtension($extensions);
    }

    /**
     * @dataProvider processorStatisticsDataProvider
     *
     * @param array $messages
     * @param mixed $expected
     */
    public function testCollectProcessorStatistics(array $messages, $expected)
    {
        $producer = $this->getContainer()->get('oro_message_queue.client.message_producer');
        foreach ($messages as list($topic, $wait)) {
            $producer->send($topic, $wait ? ['wait' => $wait] : 'empty');
        }

        $this->consume();
        $summary = $this->getSummary();

        foreach ($expected as list($topic, $avg, $count)) {
            if ($count === 0) {
                $this->assertArrayNotHasKey($topic, $summary);
                continue;
            }

            $this->assertArrayHasKey($topic, $summary);
            $this->assertGreaterThanOrEqual($avg, $summary[$topic]['avgTime']);
            $this->assertEquals($count, $summary[$topic]['total']);
        }
    }

    public function processorStatisticsDataProvider()
    {
        return [
            'test1' => [
                'messages' => [
                    ['okvpn.null.topic1', 5],
                    ['okvpn.null.topic1', 5],
                    ['okvpn.null.topic2', 20],
                ],
                'expected' => [
                    ['okvpn.null.topic1', 5, 2],
                    ['okvpn.null.topic2', 20, 1],
                ]
            ],
            'test2' => [
                'messages' => [
                    ['okvpn.null.topic2', 30]
                ],
                'expected' => [
                    ['okvpn.null.topic2', 30, 1],
                    ['okvpn.null.topic1', 0, 0],
                ]
            ],
            'test3' => [
                'messages' => [
                    ['topic_okvpn.null.topic3', 1000]
                ],
                'expected' => [
                    ['okvpn.null.topic3', 1000, 1],
                    ['okvpn.null.topic1', 0, 0],
                    ['okvpn.null.topic2', 0, 0],
                ]
            ],
            'test4' => [
                'messages' => [
                    ['topic_okvpn.null.topic3', 10],
                    ['topic_okvpn.null.topic3', 10],
                    ['topic_okvpn.null.topic3', 10],
                    ['topic_okvpn.null.topic3', 10],
                    ['topic_okvpn.null.topic3', 10],
                    ['topic_okvpn.null.topic3', 10],
                    ['topic_okvpn.null.topic3', 10],
                    ['okvpn.null.topic2', 50],
                ],
                'expected' => [
                    ['okvpn.null.topic3', 10, 7],
                    ['okvpn.null.topic2', 50, 1],
                ]
            ],
        ];
    }

    protected function consume()
    {
        $this->consumer->consume($this->extension);
    }

    protected function getSummary()
    {
        $manager = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $qb = $manager->createQueryBuilder();

        $qb
            ->select(
                [
                    '1000 * AVG(p.avgTime) as avgTime',
                    'SUM(p.ack + p.reject + p.requeue) as total',
                    'p.name'
                ]
            )
            ->from('OkvpnMQInsightBundle:ProcessorStat', 'p')
            ->groupBy('p.name');

        $summary = [];
        foreach ($qb->getQuery()->getResult() as $item) {
            $summary[$item['name']] = $item;
        }

        return $summary;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->extension, $this->consumer);
    }
}
