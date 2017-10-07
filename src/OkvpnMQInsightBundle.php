<?php

namespace Okvpn\Bundle\MQInsightBundle;

use Okvpn\Bundle\MQInsightBundle\DependencyInjection\CompilerPass\StatMessageQueuePass;
use Okvpn\Bundle\MQInsightBundle\Model\AppConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OkvpnMQInsightBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new StatMessageQueuePass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        // To avoid conflicts when using multiple applications on a single server
        AppConfig::setToken(realpath($this->container->getParameter('kernel.root_dir')));
    }
}
