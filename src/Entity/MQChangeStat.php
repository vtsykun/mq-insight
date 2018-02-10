<?php

namespace Okvpn\Bundle\MQInsightBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MQChangeStat
 *
 * @ORM\Table(name="okvpn_mq_change_stat")
 * @ORM\Entity()
 */
class MQChangeStat
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
     * @ORM\Column(name="added", type="integer")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="removed", type="integer")
     */
    private $removed;

    /**
     * @var string
     *
     * @ORM\Column(name="channel", type="string", length=32, nullable=true)
     */
    private $channel;


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
     * @return MQChangeStat
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
     * Set added
     *
     * @param integer $added
     *
     * @return MQChangeStat
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return int
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set removed
     *
     * @param integer $removed
     *
     * @return MQChangeStat
     */
    public function setRemoved($removed)
    {
        $this->removed = $removed;

        return $this;
    }

    /**
     * Get removed
     *
     * @return int
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * Set channel
     *
     * @param string $channel
     *
     * @return MQChangeStat
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }
}

