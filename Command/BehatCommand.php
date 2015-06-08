<?php

namespace CanalTP\NmpAcceptanceTestBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use CanalTP\NmpAcceptanceTestBundle\Behat\MinkExtension\Context\MinkContext;
use CanalTP\NmpAcceptanceTestBundle\Behat\MinkExtension\Context\TraceContext;
use CanalTP\NmpAcceptanceTestBundle\Behat\Behat\ApplicationFactory;

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
    public static $args = array('suite', 'profile');

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('behat:execute')
            ->setDescription('Call Behat with additional options.');
        foreach (self::$options as $option) {
            $this->addOption($option, null, InputOption::VALUE_OPTIONAL, 'Website '.$option.'.');
        }
        $this->addOption('no-jdr', null, InputOption::VALUE_OPTIONAL, 'Disable the JDR.');
        $this->addOption('trace', null, InputOption::VALUE_OPTIONAL, 'Trace output types.');
        foreach (self::$args as $arg) {
            $this->addOption($arg, null, InputOption::VALUE_OPTIONAL, 'Original argument "--'.$arg.'" of Behat.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        MinkContext::$allowed = array(
            'clients' => $this->getContainer()->getParameter('behat.clients'),
            'servers' => $this->getContainer()->getParameter('behat.servers'),
            'locales' => $this->getContainer()->getParameter('behat.locales'),
        );
        MinkContext::$options = $this->getContainer()->getParameter('behat.options');
        foreach (self::$options as $option) {
            if ($input->hasParameterOption('--'.$option)) {
                MinkContext::$options[$option] = $input->getParameterOption('--'.$option);
            }
        }
        if ($input->hasParameterOption('--no-jdr')) {
            /* TODO */
        }
        if ($input->hasParameterOption('--trace')) {
            TraceContext::$outputTypes = explode('|', $input->getParameterOption('--trace'));
        }
        $args = array();
        foreach (self::$args as $arg) {
            if ($input->hasParameterOption('--'.$arg)) {
                $args['--'.$arg] = $input->getParameterOption('--'.$arg);
            }
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
        $container = $this->getContainer();
        $rootDir = $container->getParameter('kernel.root_dir');
        define('BEHAT_BIN_PATH', $rootDir.'/../bin/behat');
        if ((!$loader = $this->includeIfExists($rootDir.'/../vendor/autoload.php')) && (!$loader = $this->includeIfExists($rootDir.'/../../../../autoload.php'))) {
            fwrite(
                STDERR,
                'You must set up the project dependencies, run the following commands:'.PHP_EOL.'curl -s http://getcomposer.org/installer | php'.PHP_EOL.'php composer.phar install'.PHP_EOL
            );
            exit(1);
        }
        $testCasesLoader = $container->get('canaltp.test_cases_loader');
        $testCases = $testCasesLoader->getTestCases(MinkContext::$options['client']);
        $factory = new ApplicationFactory($testCases);
        $factory->createApplication()->run(new ArrayInput($args));
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
