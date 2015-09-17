<?php

namespace CanalTP\AcceptanceTestBundle\Behat\Behat;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Behat\Definition\ServiceContainer\DefinitionExtension;
use Behat\Behat\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Behat\Hook\ServiceContainer\HookExtension;
use Behat\Behat\Output\ServiceContainer\Formatter\JUnitFormatterFactory;
use Behat\Behat\Output\ServiceContainer\Formatter\PrettyFormatterFactory;
use Behat\Behat\Output\ServiceContainer\Formatter\ProgressFormatterFactory;
use Behat\Behat\Snippet\ServiceContainer\SnippetExtension;
use Behat\Behat\Transformation\ServiceContainer\TransformationExtension;
use Behat\Behat\Translator\ServiceContainer\GherkinTranslationsExtension;
use Behat\Testwork\ApplicationFactory as BaseFactory;
use Behat\Testwork\Argument\ServiceContainer\ArgumentExtension;
use Behat\Testwork\Autoloader\ServiceContainer\AutoloaderExtension;
use Behat\Testwork\Call\ServiceContainer\CallExtension;
use Behat\Testwork\Cli\ServiceContainer\CliExtension;
use Behat\Testwork\Environment\ServiceContainer\EnvironmentExtension;
use Behat\Testwork\Exception\ServiceContainer\ExceptionExtension;
use Behat\Testwork\Filesystem\ServiceContainer\FilesystemExtension;
use Behat\Testwork\Output\ServiceContainer\Formatter\FormatterFactory;
use Behat\Testwork\Output\ServiceContainer\OutputExtension;
use Behat\Testwork\ServiceContainer\ServiceProcessor;
use Behat\Testwork\Specification\ServiceContainer\SpecificationExtension;
use Behat\Testwork\Suite\ServiceContainer\SuiteExtension;
use Behat\Testwork\Translator\ServiceContainer\TranslatorExtension;
use CanalTP\AcceptanceTestBundle\Behat\Behat\Gherkin\ServiceContainer\GherkinExtension;
use CanalTP\AcceptanceTestBundle\Behat\Behat\Tester\ServiceContainer\TesterExtension;

/**
 * Defines the way behat is created.
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
final class ApplicationFactory extends BaseFactory
{
    const VERSION = '3.0-dev';

    /**
     * Test cases
     *
     * @var array $testCases
     */
    private $testCases = array();

    /**
     * Constructor with test cases
     *
     * @param array $testCases
     */
    public function __construct(array $testCases)
    {
        $this->testCases = $testCases;
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return 'behat';
    }

    /**
     * {@inheritdoc}
     */
    protected function getVersion()
    {
        return self::VERSION;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultExtensions()
    {
        $processor = new ServiceProcessor();

        return array(
            new ArgumentExtension(),
            new AutoloaderExtension(array('' => '%paths.base%/features/bootstrap')),
            new SuiteExtension($processor),
            new OutputExtension('pretty', $this->getDefaultFormatterFactories($processor), $processor),
            new ExceptionExtension($processor),
            new GherkinExtension($processor),
            new CallExtension($processor),
            new TranslatorExtension(),
            new GherkinTranslationsExtension(),
            new TesterExtension($processor),
            new CliExtension($processor),
            new EnvironmentExtension($processor),
            new SpecificationExtension($processor),
            new FilesystemExtension(),
            new ContextExtension($processor),
            new SnippetExtension($processor),
            new DefinitionExtension($processor),
            new EventDispatcherExtension($processor),
            new HookExtension(),
            new TransformationExtension($processor),
            new GherkinExtension($this->testCases),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnvironmentVariableName()
    {
        return 'BEHAT_PARAMS';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigPath()
    {
        $cwd = rtrim(getcwd(), DIRECTORY_SEPARATOR);
        $paths = array_filter(
            array(
                $cwd.DIRECTORY_SEPARATOR.'behat.yml',
                $cwd.DIRECTORY_SEPARATOR.'behat.yml.dist',
                $cwd.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'behat.yml',
                $cwd.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'behat.yml.dist',
            ),
            'is_file'
        );

        if (count($paths)) {
            return current($paths);
        }

        return null;
    }

    /**
     * Returns default formatter factories.
     *
     * @param ServiceProcessor $processor
     *
     * @return FormatterFactory[]
     */
    private function getDefaultFormatterFactories(ServiceProcessor $processor)
    {
        return array(
            new PrettyFormatterFactory($processor),
            new ProgressFormatterFactory($processor),
            new JUnitFormatterFactory(),
        );
    }
}
