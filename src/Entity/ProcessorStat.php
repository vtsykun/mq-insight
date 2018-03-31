<?php

namespace Okvpn\Bundle\MQInsightBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProcessorStat
 *
 * @ORM\Table(name="okvpn_mq_processor_stat")
 * @ORM\Entity(repositoryClass="Okvpn\Bundle\MQInsightBundle\Entity\Repository\ProcessorStatRepository")
 */
class ProcessorStat
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="avg_time", type="decimal", precision=7, scale=3, nullable=true)
     */
    private $avgTime;

    /**
     * @var string
     *
     * @ORM\Column(name="max_time", type="decimal", precision=7, scale=3, nullable=true)
     */
    private $maxTime;

    /**
     * @var string
     *
     * @ORM\Column(name="min_time", type="decimal", precision=7, scale=3, nullable=true)
     */
    private $minTime;

    /**
     * @var int
     *
     * @ORM\Column(name="ack", type="integer")
     */
    private $ack;

    /**
     * @var int
     *
     * @ORM\Column(name="reject", type="integer")
     */
    private $reject;

    /**
     * @var int
     *
     * @ORM\Column(name="requeue", type="integer")
     */
    private $requeue;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer", nullable=true)
     */
    private $priority;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     *
     * @return ProcessorStat
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ProcessorStat
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set avgTime
     *
     * @param string $avgTime
     *
     * @return ProcessorStat
     */
    public function setAvgTime($avgTime)
    {
        $this->avgTime = $avgTime;

        return $this;
    }

    /**
     * Get avgTime
     *
     * @return string
     */
    public function getAvgTime()
    {
        return $this->avgTime;
    }

    /**
     * Set maxTime
     *
     * @param string $maxTime
     *
     * @return ProcessorStat
     */
    public function setMaxTime($maxTime)
    {
        $this->maxTime = $maxTime;

        return $this;
    }

    /**
     * Get maxTime
     *
     * @return string
     */
    public function getMaxTime()
    {
        return $this->maxTime;
    }

    /**
     * Set minTime
     *
     * @param string $minTime
     *
     * @return ProcessorStat
     */
    public function setMinTime($minTime)
    {
        $this->minTime = $minTime;

        return $this;
    }

    /**
     * Get minTime
     *
     * @return string
     */
    public function getMinTime()
    {
        return $this->minTime;
    }

    /**
     * Set ack
     *
     * @param integer $ack
     *
     * @return ProcessorStat
     */
    public function setAck($ack)
    {
        $this->ack = $ack;

        return $this;
    }

    /**
     * Get ack
     *
     * @return int
     */
    public function getAck()
    {
        return $this->ack;
    }

    /**
     * Set reject
     *
     * @param integer $reject
     *
     * @return ProcessorStat
     */
    public function setReject($reject)
    {
        $this->reject = $reject;

        return $this;
    }

    /**
     * Get reject
     *
     * @return int
     */
    public function getReject()
    {
        return $this->reject;
    }

    /**
     * Set requeue
     *
     * @param integer $requeue
     *
     * @return ProcessorStat
     */
    public function setRequeue($requeue)
    {
        $this->requeue = $requeue;

        return $this;
    }

    /**
     * Get requeue
     *
     * @return int
     */
    public function getRequeue()
    {
        return $this->requeue;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return ProcessorStat
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }
}
