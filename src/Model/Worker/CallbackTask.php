<?php
declare(strict_types=1);

namespace Okvpn\Bundle\MQInsightBundle\Model\Worker;

class CallbackTask implements TaskInterface
{
    protected $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return ($this->callback)(...func_get_args());
    }
}
