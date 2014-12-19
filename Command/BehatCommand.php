<?php

namespace CanalTP\NmpAcceptanceTestBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\Container;
use CanalTP\NmpAcceptanceTestBundle\Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\ApplicationFactory;

/**
 * Behat command with additional options (--client, --server, --locale, --no-jdr)
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class BehatCommand extends ContainerAwareCommand
{
    /**
     * Behat additional options
     * @var array $options
     */
    public static $options = array('client', 'server', 'locale');
    
    /**
     * Behat core args
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
            'locales' => $this->getContainer()->getParameter('behat.locales')
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
     * @param Container $container
     */
    private function runBehatCommand(array $args = array()) {
        define('BEHAT_BIN_PATH', $this->getContainer()->getParameter('kernel.root_dir').'/../bin/behat');
        function includeIfExists($file)
        {
            if (file_exists($file)) {
                return include $file;
            }
        }
        if ((!$loader = includeIfExists($this->getContainer()->getParameter('kernel.root_dir').'/../vendor/autoload.php')) && (!$loader = includeIfExists($container->getParameter('kernel.root_dir').'/../../../../autoload.php'))) {
            fwrite(STDERR,
                'You must set up the project dependencies, run the following commands:'.PHP_EOL.
                'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
                'php composer.phar install'.PHP_EOL
            );
            exit(1);
        }
        $factory = new ApplicationFactory();
        $factory->createApplication()->run(new ArrayInput($args));
    }
}
