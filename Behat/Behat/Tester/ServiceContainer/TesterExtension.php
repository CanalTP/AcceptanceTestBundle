<?php

namespace CanalTP\AcceptanceTestBundle\Behat\Behat\Tester\ServiceContainer;

use Behat\Behat\Definition\ServiceContainer\DefinitionExtension;
use Behat\Testwork\Call\ServiceContainer\CallExtension;
/*use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\Environment\ServiceContainer\EnvironmentExtension;
use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;*/
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Behat\Behat\Tester\ServiceContainer\TesterExtension as BaseExtension;

/**
 * Provides gherkin testers.
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class TesterExtension extends BaseExtension
{
    /**
     * Loads step tester.
     *
     * @param ContainerBuilder $container
     */
    protected function loadStepTester(ContainerBuilder $container)
    {
        $definition = new Definition('CanalTP\AcceptanceTestBundle\Behat\Behat\Tester\Runtime\RuntimeStepTester', array(
            new Reference(DefinitionExtension::FINDER_ID),
            new Reference(CallExtension::CALL_CENTER_ID)
        ));
        $container->setDefinition(self::STEP_TESTER_ID, $definition);
    }
}
