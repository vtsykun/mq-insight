<?php

namespace Okvpn\Bundle\MQInsightBundle\Model\Worker;

interface TaskInterface
{
    /**
     * @return bool
     */
    public function run();
}
