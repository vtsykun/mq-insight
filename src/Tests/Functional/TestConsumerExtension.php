<?php

namespace Okvpn\Bundle\MQInsightBundle\Tests\Functional;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class TestConsumerExtension extends AbstractExtension
{
    /**
     * @var callable
     */
    protected $callable;

    /**
     * @param callable $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        call_user_func($this->callable, 'onBeforeReceive', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        call_user_func($this->callable, 'onPreReceived', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        call_user_func($this->callable, 'onPostReceived', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        call_user_func($this->callable, 'onIdle', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        call_user_func($this->callable, 'onInterrupted', $context);
    }
}
