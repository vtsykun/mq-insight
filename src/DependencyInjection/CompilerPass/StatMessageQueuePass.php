<?php

namespace Okvpn\Bundle\MQInsightBundle\DependencyInjection\CompilerPass;

use Okvpn\Bundle\MQInsightBundle\Model\Provider\AbstractConnectionAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class StatMessageQueuePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $defaultProvider = $container->getDefinition('okvpn_mq_insight.queue_provider');
        foreach ($container->findTaggedServiceIds('okvpn.mq_stat_provider') as $serviceId => list($tag)) {
            if (!isset($tag['driver'])) {
                throw new \InvalidArgumentException('Driver parameters is required for tag "okvpn.mq_stat_provider"');
            }

            $service = sprintf('oro_message_queue.transport.%s.connection', $tag['driver']);
            if ($container->hasDefinition($service)) {
                $defaultProvider->replaceArgument(0, new Reference($serviceId));
                $providerDef = $container->getDefinition($serviceId);
                if (is_subclass_of($providerDef->getClass(), AbstractConnectionAwareInterface::class)) {
                    $providerDef->addMethodCall('setConnection', [new Reference($service)]);
                }
            }
        }
    }
}
