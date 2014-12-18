<?php

namespace Voryx\ThruwayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('voryx_thruway');

        $rootNode
            ->children()
                ->scalarNode('realm')->defaultValue('realm1')->end()
                ->scalarNode('uri')->defaultValue('ws://127.0.0.1:8080')->end()
                ->scalarNode('trusted_uri')->defaultValue('ws://127.0.0.1:8080')->info('Internal URI that does not require authentication')->end()
                ->booleanNode('enable_logging')->defaultFalse()->end()
                ->scalarNode('user_provider')->info('use fos_user.user_manager or in_memory_user_provider')->end()
            ->end();

        $this->addLocationsSection($rootNode);
        $this->addSupervisorSection($rootNode);
        $this->addRouterSection($rootNode);

        return $treeBuilder;
    }

    private function addLocationsSection(ArrayNodeDefinition $rootNode){
        $rootNode
            ->children()
                ->arrayNode('locations')
                ->addDefaultsIfNotSet()
                ->info('Locations of the files that need to be scan for the Thruway annotations')
                    ->children()
                        ->arrayNode('bundles')
                            ->beforeNormalization()
                                ->ifString()
                                ->then(function($v) {
                                    return preg_split('/\s*,\s*/', $v);
                                })
                            ->end()
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('files')->prototype('scalar')->end()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSupervisorSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('supervisor')
                    ->addDefaultsIfNotSet()
                    ->info('supervisor configuration')
                    ->children()
                        ->scalarNode('hostname')->defaultValue('unix:///tmp/supervisor.sock')->end()
                        ->scalarNode('executable')->defaultValue('supervisord')->end()
                        ->scalarNode('config')->defaultValue('@VoryxThruwayBundle/Resources/config/supervisord.conf')->end()
                        ->scalarNode('pidfile')->defaultValue('/tmp/supervisord.pid')->end()
                        ->scalarNode('logfile')->defaultValue('supervisord.log')->end()
                        ->scalarNode('workers')->defaultValue(5)->end()
                        ->scalarNode('port')->defaultValue(-1)->end()
                        ->scalarNode('timeout')->defaultNull()->end()
                        ->scalarNode('username')->defaultNull()->end()
                        ->scalarNode('password')->defaultNull()->end()
                        ->arrayNode('onetime_workers')->prototype('scalar')->end()->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    private function addRouterSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('router')
                ->addDefaultsIfNotSet()
                ->info('router configuration')
                    ->children()
                        ->scalarNode('ip')->defaultValue('127.0.0.1')->end()
                        ->scalarNode('port')->defaultValue('8080')->end()
                        ->scalarNode('trusted_port')->defaultValue('8081')->end()
                        ->scalarNode('authentication')->defaultFalse()->end()
                        ->booleanNode('enable_manager')->defaultFalse()->end()
                        ->booleanNode('enable_web_push')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();
    }
}
