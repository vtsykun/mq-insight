<?php

namespace Okvpn\Bundle\MQInsightBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('okvpn_mq_insight');
        $rootNode
            ->children()
                ->booleanNode('disable_demand_profiling')
                    ->info('Disable profiling on demand (run the command "okvpn:stat:retrieve" on background)')
                    ->defaultFalse()
                ->end()
                ->scalarNode('clear_stat_interval')
                    ->info('Clear data interval from okvpn_mq_processor_stat table')
                    ->example('-10 days')
                    ->defaultValue('-5 days')
                ->end()
                ->scalarNode('clear_error_interval')
                    ->info('Clear data interval from okvpn_mq_error_stat table')
                    ->example('-60 days')
                    ->defaultValue('-30 days')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
