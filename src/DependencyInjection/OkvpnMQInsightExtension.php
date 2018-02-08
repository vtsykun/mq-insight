<?php

namespace Okvpn\Bundle\MQInsightBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class OkvpnMQInsightExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('storage.yml');
        $loader->load(extension_loaded('amqp') ? 'ext-amqp.yml' : 'php-amqp.yml');

        // todo temporary implementation, so replace on config in future
        if (false && extension_loaded('sysvshm')) {
            $container->setAlias('okvpn_mq_insight.storage', 'okvpn_mq_insight.storage.shared_memory');
        } else {
            $container->setAlias('okvpn_mq_insight.storage', 'okvpn_mq_insight.storage.file_system');
        }

        $env = $container->getParameter('kernel.environment');
        if ($env === 'test') {
            $loader->load('tests.yml');
        }
    }
}
