<?php

namespace Okvpn\Bundle\MQInsightBundle\Manager;

trait GetOsTrait
{
    /**
     * @return string
     */
    protected function getOs(): string
    {
        return explode(' ', strtoupper(php_uname()))[0];
    }
}
