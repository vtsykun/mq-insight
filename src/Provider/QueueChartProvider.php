<?php

namespace Okvpn\Bundle\MQInsightBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

class QueueChartProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \DateTime $fetchFrom
     * @param TransformerToViewInterface|null $transformer
     * @return mixed
     */
    public function getQueueSizeData(\DateTime $fetchFrom, TransformerToViewInterface $transformer = null)
    {
        $transformer = $transformer ?? $this->getTransformToView();
        $repo = $this->registry->getRepository('OkvpnMQInsightBundle:MQStateStat');
        $data = $repo->getQueueSize($fetchFrom);

        return $transformer->transform($data);
    }

    public function getDailyStat()
    {
        $fetchFrom = new \DateTime();
        $fetchFrom->modify('-1 day');
        $repo = $this->registry->getRepository('OkvpnMQInsightBundle:MQStateStat');

        $change = $repo->getChangeCount($fetchFrom);
        $result = array_merge(
            [
                'added' => 0,
                'removed' => 0
            ],
            !empty($change) ? reset($change) : []
        );

        return array_merge(
            [
                'error' => $repo->getErrorCount($fetchFrom),
                'avgSize' => $repo->getAvgSize($fetchFrom)
            ],
            $result
        );
    }

    protected function getTransformToView()
    {
        return new class implements TransformerToViewInterface
        {
            public function transform(array $data)
            {
                if (empty($data)) {
                    return [];
                }

                $result = [];
                $prob = current($data);
                if (is_array($prob)) {
                    foreach (array_keys($prob) as $key) {
                        $items = array_column($data, $key);
                        if (current($items) instanceof \DateTime) {
                            $items = array_map(function(\DateTime $item) {return $item->format('Y-m-d\TH:i:s');}, $items);
                        }

                        $result[] = array_merge([$key], $items);
                    }
                } else {
                    $result = $data;
                }

                return $result;
            }
        };
    }
}
