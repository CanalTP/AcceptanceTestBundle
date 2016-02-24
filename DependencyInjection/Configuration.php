<?php

namespace CanalTP\AcceptanceTestBundle\DependencyInjection;

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
        $treeBuilder->root('canal_tp_acceptance_test')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('clients')
                    ->defaultValue(array())
                    ->prototype('variable')
                    ->end()
                ->end()
                ->arrayNode('servers')
                    ->defaultValue(array())
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('locales')
                    ->defaultValue(array())
                    ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('screen_sizes')
                    ->defaultValue(array(
                        'small' => array(
                            'width' => 320,
                            'height' => 580
                        ),
                        'large' => array(
                            'width' => 1600,
                            'height' => 900
                        )
                    ))
                    ->prototype('array')
                        ->children()
                            ->integerNode('width')
                                ->isRequired()
                                ->min(0)
                            ->end()
                            ->integerNode('height')
                                ->isRequired()
                                ->min(0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_screen_size')
                    ->defaultValue('large')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('client')
                            ->defaultValue('Ctp')
                        ->end()
                        ->scalarNode('server')
                            ->defaultValue('local')
                        ->end()
                        ->scalarNode('locale')
                            ->defaultValue('fr')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('timeouts')
                    ->defaultValue(array())
                    ->prototype('scalar')
                    ->end()
                    ->children()
                        ->integerNode('autocomplete')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('test_cases_path')
                    ->defaultValue('%kernel.root_dir%/config/test_cases')
                ->end()
                ->arrayNode('roles')
                    ->defaultValue(array())
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('login')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('password')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
