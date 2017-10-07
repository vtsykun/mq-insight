<?php

namespace Okvpn\Bundle\MQInsightBundle;

use Okvpn\Bundle\MQInsightBundle\DependencyInjection\CompilerPass\AddAMQPProviderPass;
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
        $container->addCompilerPass(new AddAMQPProviderPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        AppConfig::setToken(realpath($this->container->getParameter('kernel.root_dir')));
    }
}
