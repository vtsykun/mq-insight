<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Provider;

class DefaultQueueProvider implements QueueProviderInterface, RandomAccessQueueInterface
{
    /** @var QueueProviderInterface */
    protected $provider;

    /**
     * @param QueueProviderInterface $provider
     */
    public function __construct(QueueProviderInterface $provider = null)
    {
        if (null === $provider) {
            $this->provider = new NullQueueProvider();
        }

        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function queueCount()
    {
        return $this->provider->queueCount();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMessage($messageId)
    {
        if ($this->provider instanceof RandomAccessQueueInterface) {
            return $this->provider->deleteMessage($messageId);
        }

        return self::NOT_SUPPORT;
    }

    /**
     * @return QueueProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
