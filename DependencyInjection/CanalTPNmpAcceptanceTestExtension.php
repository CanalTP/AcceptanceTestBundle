<?php

namespace CanalTP\NmpAcceptanceTestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CanalTPNmpAcceptanceTestExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('behat.clients', $config['clients']);
        $container->setParameter('behat.servers', $config['servers']);
        $container->setParameter('behat.locales', $config['locales']);
        $container->setParameter('behat.options', $config['options']);
    }
}