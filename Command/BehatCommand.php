<?php

namespace CanalTP\AcceptanceTestBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use CanalTP\AcceptanceTestBundle\Behat\MinkExtension\Context\MinkContext;
use CanalTP\AcceptanceTestBundle\Behat\MinkExtension\Context\TraceContext;
use CanalTP\AcceptanceTestBundle\Behat\Behat\ApplicationFactory;

/**
 * Behat command with additional options (--client, --server, --locale, --no-jdr, --trace)
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class BehatCommand extends ContainerAwareCommand
{
    /**
     * Behat additional options
     *
     * @var array $options
     */
    public static $options = array('client', 'server', 'locale');

    /**
     * Behat core args
     *
     * @var array $args
     */
    public static $args = array('suite', 'profile', 'tags');

    /**
     * Container
     *
     * @var ContainerInterface $container
     */
    private $container;

    /**
     * JDR
     *
     * @var boolean $jdr
     */
    private $jdr;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('behat:execute')
            ->setDescription('Call Behat with additional options.');
        foreach (self::$options as $option) {
            $this->addOption($option, null, InputOption::VALUE_OPTIONAL, 'Website ' . $option . '.');
        }
        $this->addArgument('paths', InputArgument::OPTIONAL, 'Optional path(s) to execute.');
        $this->addOption('no-jdr', null, InputOption::VALUE_OPTIONAL, 'Disables the JDR.');
        $this->addOption('trace', null, InputOption::VALUE_OPTIONAL, 'Trace output types.');
        $this->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'Saves page content on scenario fail.', false);
        foreach (self::$args as $arg) {
            $this->addOption($arg, null, InputOption::VALUE_OPTIONAL, 'Original argument "--' . $arg . '" of Behat.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getContainer();
        MinkContext::$allowed = array(
            'clients' => $this->container->getParameter('behat.clients'),
            'servers' => $this->container->getParameter('behat.servers'),
            'locales' => $this->container->getParameter('behat.locales'),
            'screen_sizes' => $this->container->getParameter('behat.screen_sizes'),
        );
        MinkContext::$options = $this->container->getParameter('behat.options');
        foreach (self::$options as $option) {
            if ($input->hasParameterOption('--' . $option)) {
                MinkContext::$options[$option] = $input->getParameterOption('--' . $option);
            }
        }
        MinkContext::$jdr = !$input->hasParameterOption('--no-jdr');
        if ($input->hasParameterOption('--trace')) {
            TraceContext::$outputTypes = explode('|', $input->getParameterOption('--trace'));
        }
        TraceContext::$enableDebug = $input->hasParameterOption('--debug');
        $args = array('behat');
        foreach (self::$args as $arg) {
            if ($input->hasParameterOption('--' . $arg)) {
                $args[] = '--' . $arg . '=' . $input->getParameterOption('--' . $arg);
            }
        }

        if ($input->hasArgument('paths')) {
            $args[] = $input->getArgument('paths');
        }

        $this->runBehatCommand($args);
    }

    /**
     * Run behat original command
     *
     * @param array $args
     */
    private function runBehatCommand(array $args = array())
    {
        $rootDir = $this->container->getParameter('kernel.root_dir');
        define('BEHAT_BIN_PATH', $rootDir . '/../bin/behat');
        if ((!$loader = $this->includeIfExists($rootDir . '/../vendor/autoload.php')) && (!$loader = $this->includeIfExists($rootDir . '/../../../../autoload.php'))) {
            fwrite(
                STDERR,
                'You must set up the project dependencies, run the following commands:' . PHP_EOL . 'curl -s http://getcomposer.org/installer | php' . PHP_EOL . 'php composer.phar install' . PHP_EOL
            );
            exit(1);
        }
        $testCases = array();
        if ($this->container->getParameter('behat.test_cases_path')) {
            $testCasesLoader = $this->container->get('canaltp.test_cases_loader');
            $testCases = $testCasesLoader->getTestCases(
                MinkContext::$jdr ? 'jdr' : MinkContext::$options['client']
            );
        }
        $factory = new ApplicationFactory($testCases);
        $factory->createApplication()->run(new ArgvInput($args));
    }

    /**
     * File includer
     *
     * @param string $file
     * @return type
     */
    private function includeIfExists($file)
    {
        if (file_exists($file)) {
            return include $file;
        }
    }
}
