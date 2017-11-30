<?php

namespace Okvpn\Bundle\MQInsightBundle\Controller;

use Okvpn\Bundle\MQInsightBundle\Entity\MQStateStat;
use Okvpn\Bundle\MQInsightBundle\Manager\ProcessManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $data = $this->get('okvpn_mq_insight.chart_provider')->getQueueSizeData($fetchFrom);

        $runningConsumers = ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume');
        /** @var MQStateStat $size */
        $size = $this->get('doctrine')
            ->getRepository('OkvpnMQInsightBundle:MQStateStat')
            ->getLastValue();

        $dailyStat = $this->get('okvpn_mq_insight.chart_provider')->getDailyStat();

        return [
            'entity' => new MQStateStat(),
            'sizeData' => $data,
            'fetchFrom' => $fetchFrom->format('c'),
            'running' => $runningConsumers,
            'runningCount' => count($runningConsumers),
            'size' => $size ? $size->getQueue() : null,
            'dailyStat' => $dailyStat
        ];
    }

    /**
     * @Template()
     * @Route("/info", name="okvpn_mq_insight_plot")
     * @AclAncestor("message_queue_view_stat")
     */
    public function plotAction()
    {
        return [];
    }

    /**
     * @Route("/queued", name="okvpn_mq_insight_queued")
     * @AclAncestor("message_queue_view_stat")
     *
     * @param Request $request
     * @return Response
     */
    public function queuedAction(Request $request)
    {
        $result = $this->get('okvpn_mq_insight.queued_messages_provider')->getQueuedMessages();

        $runningConsumers = ProcessManager::getPidsOfRunningProcess('oro:message-queue:consume');
        if ($request->get('isLast')) {
            $result = end($result) ?: [];
        }

        return new JsonResponse(
            [
                'runningConsumers' => $runningConsumers,
                'queued' => $result,
                'size' => $this->get('okvpn_mq_insight.queue_provider')->getApproxQueueCount()
            ]
        );
    }
}
