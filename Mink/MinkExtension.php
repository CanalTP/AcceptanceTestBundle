<?php
namespace CanalTP\NmpAcceptanceTestBundle\Mink;

use Behat\MinkExtension\ServiceContainer\MinkExtension as BaseMinkExtension;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Mink extension override with parameters for the cli command (--customer, --server, --locale)
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class MinkExtension extends BaseMinkExtension
{    
    /**
     * {@inheritDoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        parent::configure($builder);
        $builder
            ->children()
                ->arrayNode('options')
                    ->children()
                        ->scalarNode('client')
                        ->end()
                        ->scalarNode('server')
                        ->end()
                        ->scalarNode('locale')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
    
    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $options = $config['options'];
        $config['base_url'] = strtr('http://nmp-ihm.'.strtolower($options['client']).'.'.strtolower($options['server']).'.canaltp.fr/'.$options['locale'], array(' ', ''));
        parent::load($container, $config);
    }
}
