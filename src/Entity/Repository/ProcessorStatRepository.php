<?php

namespace Okvpn\Bundle\MQInsightBundle\Entity\Repository;

/**
 * ProcessorStatRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProcessorStatRepository extends \Doctrine\ORM\EntityRepository
{
    public function summaryStat(\DateTime $from)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.avgTime*(p.ack + p.reject + p.requeue))')
            ->where('p.created > :from')
            ->andWhere("p.name NOT IN ('idle')");

        $sumTime = $qb->setParameter('from', $from)
            ->getQuery()->getSingleScalarResult();

        if ($sumTime > 0) {
            $qb = $this->createQueryBuilder('p')
                ->select(
                    [
                        'SUM(p.avgTime*(p.ack + p.reject + p.requeue))/(:sumTime) as totalTime',
                        'p.name'
                    ]
                )
                ->where('p.created > :from')
                ->andWhere("p.name NOT IN ('idle')")
                ->groupBy('p.name')
                ->orderBy('totalTime', 'DESC')
                ->having('SUM(p.avgTime*(p.ack + p.reject + p.requeue))/(:sumTime) > 0.005')
                ->setMaxResults(15)
                ->setParameter('from', $from)
                ->setParameter('sumTime', $sumTime);

            $data = $qb->getQuery()->getResult();
            $processors = array_column($data, 'name');

            $qb = $this->createQueryBuilder('p')
                ->select('SUM(p.avgTime*(p.ack + p.reject + p.requeue))')
                ->where('p.created > :from')
                ->andWhere("p.name NOT IN ('idle')")
                ->andWhere('p.name NOT IN (:exclude)')
                ->setParameter('from', $from)
                ->setParameter('exclude', $processors);

            $otherProcessors = $qb->getQuery()->getSingleScalarResult();
            if ($otherProcessors > 0) {
                $data[] = ['totalTime' => $otherProcessors/$sumTime, 'name' => 'other'];
            }

            return $data;
        }

        return [];
    }
}
