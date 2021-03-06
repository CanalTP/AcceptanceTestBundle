<?php

namespace CanalTP\AcceptanceTestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CanalTPAcceptanceTestExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\XMLFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');

        $container->setParameter('behat.clients', $config['clients']);
        $container->setParameter('behat.servers', $config['servers']);
        $container->setParameter('behat.locales', $config['locales']);
        $container->setParameter('behat.screen_sizes', $config['screen_sizes']);
        $container->setParameter('behat.default_screen_size', $config['default_screen_size']);
        $container->setParameter('behat.options', $config['options']);
        $container->setParameter('behat.timeouts', $config['timeouts']);
        $container->setParameter('behat.test_cases_path', $config['test_cases_path']);
        $container->setParameter('behat.roles', $config['roles']);
        $container->setParameter('at.ui_scan_dir', $config['ui_scan_dir']);
    }
}
