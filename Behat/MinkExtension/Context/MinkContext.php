<?php

namespace CanalTP\NmpAcceptanceTestBundle\Behat\MinkExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Symfony\Component\HttpKernel\KernelInterface;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Component\Yaml\Parser;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;

/**
 * Mink context for Behat BDD tool.
 * Provides Mink integration and base step definitions with additional options.
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class MinkContext extends TraceContext implements SnippetAcceptingContext, KernelAwareContext
{
    /**
     * Behat additional options
     *
     * @var array $options
     */
    public static $options;
    /**
     * Allowed values for addtional options
     *
     * @var array $allowed
     */
    public static $allowed;
    /**
     * Use cases
     *
     * @var array $useCases
     */
    protected $useCases;
    /**
     * Timeouts
     *
     * @var array $timeouts
     */
    protected $timeouts;
    /**
     * Application Kernel
     *
     * @var KernelInterface $kernel
     */
    private $kernel;

    /**
     * @BeforeScenario
     * @param BeforeScenarioScope $event
     */
    public function beforeScenario(BeforeScenarioScope $event)
    {
        $this->getSession()->reset();
        parent::beforeScenario($event);
    }

    /**
     * {@inheritdoc}
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $container = $this->kernel->getContainer();
        if (self::$options === null) {
            self::$options = $container->getParameter('behat.options');
        }
        if (self::$allowed === null) {
            self::$allowed = array(
                'clients' => $container->getParameter('behat.clients'),
                'servers' => $container->getParameter('behat.servers'),
                'locales' => $container->getParameter('behat.locales'),
            );
        }
        $this->timeouts = $container->getParameter('behat.timeouts');
    }

    /**
     * Behat additional options initializer
     */
    public function __construct()
    {
        $this->forTheClient(self::$options['client'], self::$options['server'], self::$options['locale']);
    }

    /**
     * Log with a role
     *
     * @Given /^(?:|I am )logged as "(?P<role>(?:[^"]|\\")*)"$/
     */
    public function logAs($role)
    {
        switch ($role) {
            case 'super_admin':
                break;
            case 'admin':
                break;
            case 'visitor':
                break;
            case 'translator':
                break;
            case 'user':
                break;
        }
    }

    /**
     * Using a specific server, client and locale
     *
     * @Given /^(?:|(?:|I am )on "(?P<server>(?:[^"]|\\")*)" )for the client "(?P<client>(?:[^"]|\\")*)"(?:| in "(?P<locale>(?:[^"]|\\")*)")$/
     */
    public function forTheClient($client, $server = null, $locale = null)
    {
        if (!in_array($client, self::$allowed['clients'])) {
            throw new \Exception('Website client "'.$client.'" not found.');
        }
        if (!in_array($server, self::$allowed['servers']) && !empty($server)) {
            throw new \Exception('Website server "'.$server.'" not found.');
        } else {
            $server = self::$options['server'];
        }
        if ($locale !== '' && !in_array($locale, self::$allowed['locales']) && !empty($locale)) {
            throw new \Exception('Website locale "'.$locale.'" not found.');
        } elseif ($locale !== '') {
            $locale = self::$options['locale'];
        }
        $baseUrl = 'http://nmp-ihm.'.strtolower($client).'.'.strtolower($server).'.canaltp.fr';
        if (trim($locale) !== '') {
            $baseUrl .= '/'.$locale;
        }
        $this->setMinkParameter('base_url', strtr($baseUrl, array(' ', '')));
    }

    /**
     * Enable or disable JS
     *
     * @Given /^With(?P<suffix>(?:|out)) Javascript$/
     */
    public function withJavascript($suffix)
    {
        if ($suffix == 'out') {
            // Use Goutte (default: Selenium)
        }
    }

    /**
     * Click on element with specified CSS
     *
     * @When /^(?:|I )click on "(?P<id>(?:[^"]|\\")*)"$/
     */
    public function clickOn($element)
    {
        $this->assertSession()->elementExists('css', $element)->click();
    }

    /**
     * Checks, that element with specified CSS is visible on page.
     *
     * @Then /^(?:|The )"(?P<element>[^"]*)" element (should be|is) visible$/
     */
    public function assertElementVisible($element)
    {
        if (!$this->assertSession()->elementExists('css', $element)->isVisible()) {
            throw new \Exception('Element "'.$element.'" not visible.');
        }
    }

    /**
     * Checks, that element with specified CSS is not visible on page.
     *
     * @Then /^(?:|The )"(?P<element>[^"]*)" element (should not be|is not) visible$/
     */
    public function assertElementNotVisible($element)
    {
        if ($this->assertSession()->elementExists('css', $element)->isVisible()) {
            throw new \Exception('Element "'.$element.'" visible.');
        }
    }

    /**
     * Checks, that element children with specified CSS are on page.
     *
     * @param string $element
     * @param array $children
     */
    public function assertElementChildrenOnPage($element, $children = array())
    {
        foreach ($children as $child) {
            $this->assertElementOnPage($element.' '.$child);
        }
    }

    /**
     * Checks, that element children with specified CSS are not on page.
     *
     * @param string $element
     * @param array $children
     */
    public function assertElementChildrenNotOnPage($element, $children = array())
    {
        foreach ($children as $child) {
            $this->assertElementNotOnPage($element.' '.$child);
        }
    }

    /**
     * Checks, that element childrens with specified CSS are visible on page.
     *
     * @param string $element
     * @param array $childrens
     */
    public function assertElementChildrensVisible($element, $childrens = array())
    {
        foreach ($childrens as $children) {
            $this->assertElementVisible($element.' '.$children);
        }
    }

    /**
     * Checks, that element childrens with specified CSS are not visible on page.
     *
     * @param string $element
     * @param array $childrens
     */
    public function assertElementChildrensNotVisible($element, $childrens = array())
    {
        foreach ($childrens as $children) {
            $this->assertElementNotVisible($element.' '.$children);
        }
    }

    /**
     * Check an object parameter existance
     *
     * @Then /^(?:|The )"(?P<property>[^"]*)" property should exists$/
     */
    public function assertPropertyExists($property, $subject = null)
    {
        $object = null;
        switch (gettype($subject)) {
            case 'object':
                $object = $subject;
                break;
            case 'array':
                $object = json_decode(json_encode($subject), false);
                break;
            case 'NULL':
                $subject = $this->getSession()->getPage()->getText();
                // Default subject value used in the next case without break;
            case 'string':
                $object = json_decode($subject);
                break;
            default:
                throw new \Exception('Object format not supported.');
        }
        if (!property_exists($object, $property)) {
            throw new \Exception('Object property not found.');
        }

        return $object->$property;
    }

    /**
     * Redirection to an url
     *
     * @Then /^(?:|I am )redirected to "(?P<page>[^"]*)"$/
     */
    public function redirectedTo($page)
    {
        $this->assertPageAddress($page);
    }

    /**
     * Cookie creator
     *
     * @Then /^(?:|I have )a cookie "(?P<name>[^"]*)" with value "(?P<value>[^"]*)"$/
     */
    public function cookieWithValue($name, $value)
    {
        $this->getSession()->setCookie($name, $value);
    }

    /**
     * Use cases iterator by module
     *
     * @Then /^For each "(?P<module>[^"]*)" use case$/
     */
    public function forEachUseCase($module)
    {
        foreach ($this->getUseCases($module) as $index => $useCase) {
            if (!$index) {
                $useCases[] = array_keys($useCase);
            }
            $useCases[] = array_values($useCase);
        }

        return new TableNode($useCases);
    }

    /**
     * Use cases file getter
     *
     * @return string
     */
    private function getUseCasesFile()
    {
        switch (self::$options['client']) {
            case 'Ctp':
                $dir = $this->kernel->getRootdir().'/config';
                break;
            default:
                $dir = $this->kernel->getRootdir().'/../custom/'.self::$options['client'].'/Resources/config';
        }

        return $dir.'/test_cases.yml';
    }

    /**
     * Use cases getter by module
     */
    protected function getUseCases($module)
    {
        if (is_null($this->useCases)) {
            $file = $this->getUseCasesFile();
            if (!file_exists($file) || !is_file($file)) {
                throw new PendingException(sprintf('Use cases missing: %s', $file));
            }
            $parser = new Parser();
            $yaml = $parser->parse(file_get_contents($file));
            $this->useCases = !empty($yaml['canal_tp_nmp_acceptance_test']['use_cases']) ?
                $yaml['canal_tp_nmp_acceptance_test']['use_cases'] : array();
        }

        return isset($this->useCases[$module]) ? $this->useCases[$module] : array();
    }

    /**
     * Wait until function
     *
     * @param integer $timeout
     * @param Function $callback
     * @return boolean
     * @throws Exception
     */
    protected function waitFor($timeout, $callback, $parameters = array())
    {
        for ($i = 0; $i < $timeout; $i++) {
            try {
                if ($callback($this, $parameters)) {
                    return true;
                }
            } catch (Exception $e) {
            }

            sleep(1);
        }

        $backtrace = debug_backtrace();

        throw new \Exception(
            sprintf(
                'Timeout thrown by "%s::%s()" in %s, line %s',
                $backtrace[1]['class'],
                $backtrace[1]['function'],
                $backtrace[1]['file'],
                $backtrace[1]['line']
            )
        );
    }

    /**
     * Autocomplete field fill function
     * @param string $field
     * @param string $value
     * /^I fill the autocomplete field "" with value ""$/
     */
    protected function fillAutocompleteField($field, $value)
    {
        $this->getSession()->executeScript('CanalTP.jQuery("#'.$field.'").val("'.$value.'");');
        $this->getSession()->executeScript('CanalTP.jQuery("#'.$field.'").autocomplete("search");');
        $targetList = $this->assertSession()->elementExists('css', '#'.$field)->getAttribute('data-target-list');
        $this->waitFor(
            $this->timeouts['autocomplete'] / 1000,
            function ($context, $parameters) {
                return $context->assertSession()->elementExists('css', '#'.$parameters['targetList'])->isVisible();
            },
            array('targetList' => $targetList)
        );
        $this->clickOn('#'.$targetList.' .ui-autocomplete-item-0 a');
    }
}
