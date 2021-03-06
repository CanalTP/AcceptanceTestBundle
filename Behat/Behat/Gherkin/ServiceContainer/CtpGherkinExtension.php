<?php

namespace CanalTP\AcceptanceTestBundle\Behat\Behat\Gherkin\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Extends Behat with gherkin suites and features.
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
final class CtpGherkinExtension implements Extension
{
    /**
     * Test cases
     *
     * @var array $testCases
     */
    protected $testCases = array();

    /**
     * Constructor with test cases
     * @param array $testCases
     */
    public function __construct(array $testCases)
    {
        $this->testCases = $testCases;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'ctp_gherkin';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $this->loadServices($container);
        $this->loadParser($container);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadParser($container);
    }

    /**
     * Loads services.
     *
     * @param ContainerBuilder $container
     */
    private function loadServices(ContainerBuilder $container)
    {
        $definition = new Definition(
            'CanalTP\AcceptanceTestBundle\Service\ApiExamplesLoaderService'
        );
        $container->setDefinition('canaltp.api_examples_loader', $definition);
    }

    /**
     * Loads gherkin parser.
     *
     * @param ContainerBuilder $container
     */
    private function loadParser(ContainerBuilder $container)
    {
        $definition = new Definition(
            'CanalTP\AcceptanceTestBundle\Behat\Gherkin\Parser',
            array(
                new Reference('gherkin.lexer'),
                new Reference('canaltp.api_examples_loader'),
                $this->testCases
            )
        );
        $container->setDefinition('gherkin.parser', $definition);
    }
}
