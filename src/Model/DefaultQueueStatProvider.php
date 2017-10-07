<?php

namespace Okvpn\Bundle\MQInsightBundle\Model;

class DefaultQueueStatProvider implements QueueStatProviderInterface
{
    /** @var QueueStatProviderInterface */
    protected $provider;

    /**
     * @param QueueStatProviderInterface $provider
     */
    public function __construct(QueueStatProviderInterface $provider = null)
    {
        if (null === $provider) {
            $this->provider = new NullQueueStatProvider();
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
}
