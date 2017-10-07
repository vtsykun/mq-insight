<?php

namespace Okvpn\Bundle\MQInsightBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddAMQPProviderPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('okvpn_mq_insight.provider_amqp_ext')) {
            return;
        }

        /** @var OroMessageQueueExtension $extension */
        $def = $container->getDefinition('okvpn_mq_insight.provider_amqp_ext');
        $extension = $container->getExtension('oro_message_queue');
        $tmpContainer = new ContainerBuilder($container->getParameterBag());

        $config = $container->getParameterBag()->resolveValue($container->getExtensionConfig('oro_message_queue'));
        $processor = new Processor();
        $config = $processor->processConfiguration(
            $extension->getConfiguration([], $tmpContainer),
            $config
        );

        if (isset($config['transport']['amqp'])) {
            $def->replaceArgument(1, $config['transport']['amqp']);
        }
    }
}
