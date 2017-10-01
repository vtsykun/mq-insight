<?php

namespace Okvpn\Bundle\MQInsightBundle\Controller;

use Okvpn\Bundle\MQInsightBundle\Entity\MQStateStat;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/queue-status")
 */
class QueueController extends Controller
{
    /**
     * @Template()
     * @Route("/", name="okvpn_mq_insight_status")
     * @AclAncestor("message_queue_view_stat")
     */
    public function statusAction()
    {
        $fetchFrom = new \DateTime();
        $fetchFrom->modify('-1 day');
        $data = $this->get('okvpn_redis_queue.chart_provider')->getQueueSizeData($fetchFrom);

        return [
            'entity' => new MQStateStat(),
            'sizeData' => $data,
            'fetchFrom' => $fetchFrom->format('c')
        ];
    }

    /**
     * @Template()
     * @Route("/info", name="okvpn_mq_insight_info")
     * @AclAncestor("message_queue_view_stat")
     */
    public function infoAction()
    {
        $runningConsumers = ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume');
        $size = $this->get('okvpn_mq_insight.queue_provider')->queueCount();
        $dailyStat = $this->get('okvpn_redis_queue.chart_provider')->getDailyStat();

        return [
            'running' => $runningConsumers,
            'count' => count($runningConsumers),
            'size' => $size,
            'dailyStat' => $dailyStat
        ];
    }
}
