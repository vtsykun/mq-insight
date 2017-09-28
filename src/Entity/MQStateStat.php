<?php

namespace Okvpn\Bundle\MQInsightBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MQState
 *
 * @ORM\Table(name="okvpn_mq_state_stat")
 * @ORM\Entity(repositoryClass="Okvpn\Bundle\MQInsightBundle\Entity\Repository\MQStateRepository")
 */
class MQStateStat
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
     * @var int
     *
     * @ORM\Column(name="queue", type="integer")
     */
    private $queue;


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
     * @return MQState
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
     * Set queue
     *
     * @param integer $queue
     *
     * @return MQState
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Get queue
     *
     * @return int
     */
    public function getQueue()
    {
        return $this->queue;
    }
}
