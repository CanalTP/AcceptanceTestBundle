<?php

namespace CanalTP\AcceptanceTestBundle\Behat\MinkExtension\Context;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\Exception;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Symfony\Component\HttpKernel\KernelInterface;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use CanalTP\AcceptanceTestBundle\Behat\Behat\Tester\Exception\SkippedException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Element\NodeElement;
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
     * JDR state
     *
     * @var boolean $jdr
     */
    public static $jdr;
    /**
     * Timeouts
     *
     * @var array $timeouts
     */
    protected $timeouts;
    /**
     * Roles
     *
     * @var array $roles
     */
    protected $roles;
    /**
     * Application Kernel
     *
     * @var KernelInterface $kernel
     */
    protected $kernel;

    /**
     * @BeforeScenario
     * @param BeforeScenarioScope $event
     */
    public function beforeScenario(BeforeScenarioScope $event)
    {
        $this->getSession()->reset();
        // Set the default windows size as the one given in configuration
        if (!is_null($this->kernel->getContainer()->getParameter('behat.default_screen_size'))) {
            $this->onAScreenSize($this->kernel->getContainer()->getParameter('behat.default_screen_size'));
        }
        $this->getBaseUrl();
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
                'screen_sizes' => $container->getParameter('behat.screen_sizes'),
            );
        }
        $this->timeouts = $container->getParameter('behat.timeouts');
        $this->roles = $container->getParameter('behat.roles');
    }

    /**
     * Base url getter
     *
     * @return string
     * @throws \Exception
     */
    public function getBaseUrl()
    {
        if ($this->getMinkParameter('base_url') === null) {
            $this->forTheClient(self::$options['client'], self::$options['server'], self::$options['locale']);
        } else if (!is_null(self::$options['locale'])) {
            $this->inLocale(self::$options['locale']);
        }

        return $this->getMinkParameter('base_url');
    }

    /**
     * Log with a role
     *
     * @Given /^(?:|I am )logged as "(?P<role>(?:[^"]|\\")*)"$/
     */
    public function logAs($role)
    {
        if (empty($this->roles[$role])) {
            throw new \Exception(sprintf('Credentials for the role "%s" missing.', $role));
        }
        $this->visit('/login');
        $login = $this->roles[$role]['login'];
        $password = $this->roles[$role]['password'];
        $this->fillField('_username', $login);
        $this->fillField('_password', $password);
        $this->clickOn('form button[type=submit]');
        try {
            $this->redirectedTo('/user/');
        } catch (ExpectationException $e) {
            throw new \Exception(sprintf('Login with the role "%s" failure.', $role));
        }
    }

    /**
     * Using a specific server, client and locale
     *
     * @Given /^(?:|(?:|I am )on "(?P<server>(?:[^"]|\\")*)" server )for the client "(?P<client>(?:[^"]|\\")*)"(?:| in locale "(?P<locale>(?:[^"]|\\")*)")$/
     */
    public function forTheClient($client, $server = null, $locale = null)
    {
        if (!in_array($client, $this->getClients())) {
            throw new \InvalidArgumentException(sprintf(
                'Client "%s" does not exist. Available clients are: "%s".',
                $client,
                implode('", "', $this->getClients())
            ));
        }

        if (!empty($client) && $client !== self::$options['client']) {
            throw new SkippedException(
                sprintf(
                    'SKIPPED: client (%s) different than the current tested client (%s).',
                    $client,
                    self::$options['client']
                )
            );
        }
        $baseUrl = 'http://nmp-ihm.'.strtolower($client).'.'.strtolower($this->onServer($server)).'.canaltp.fr';
        $this->setMinkParameter('base_url', strtr($baseUrl, array(' ', '')));
        $this->inLocale($locale);
    }

    /**
     * Test will be skipped if current client match one of the given clients
     *
     * @Given skip for clients:
     *
     * @param TableNode $clientsTable
     */
    public function skipForClients(TableNode $clientsTable)
    {
        $clients = array();
        foreach ($clientsTable->getTable() as $table) {
            $clients[] = $table[0];
        }

        if (empty($clients)) {
            throw new \InvalidArgumentException('No client to skip defined');
        }

        if (in_array(self::$options['client'], $clients)) {
            throw new SkippedException(
                sprintf(
                    'SKIPPED: client (%s) skipped on demand.',
                    self::$options['client']
                )
            );
        }
    }

    /**
     * Using a specific design
     *
     * @Given /^for the design "(?P<design>(?:[^"]|\\")*)"$/
     */
    public function forTheDesign($design)
    {
        if (!in_array($design, array_keys(self::$allowed['clients']))) {
            throw new \InvalidArgumentException(sprintf(
                'Design "%s" does not exist. Available designs are: "%s".',
                $design,
                implode('", "', array_keys(self::$allowed['clients']))
            ));
        }
        $clientDesign = $this->getDesign(self::$options['client']);

        if (!empty($design) && $design !== $clientDesign) {
            throw new SkippedException(
                sprintf(
                    'SKIPPED: design (%s) different than the current tested design (%s).',
                    $design,
                    $clientDesign
                )
            );
        }
    }

    /**
     * Using a specific server
     *
     * @Given /^(?:|I am )on "(?P<server>(?:[^"]|\\")*)" server$/
     */
    public function onServer($server = null)
    {
        if (empty($server)) {
            $server = self::$options['server'];
        } else {
            if (!in_array($server, self::$allowed['servers'])) {
                throw new \InvalidArgumentException(sprintf(
                    'Server "%s" does not exist. Available servers are: "%s".',
                    $server,
                    implode('", "', array_values(self::$allowed['servers']))
                ));
            } elseif ($server !== self::$options['server']) {
                throw new SkippedException(
                    sprintf(
                        'SKIPPED: server (%s) different than the current server (%s).',
                        $server,
                        self::$options['server']
                    )
                );
            }
        }

        return $server;
    }

    /**
     * Using a specific locale
     *
     * @Given /^in locale "(?P<locale>(?:[^"]|\\")*)"$/
     */
    public function inLocale($locale = null)
    {
        if ($locale !== '') {
            if (empty($locale)) {
                $locale = self::$options['locale'];
            } else {
                if (!in_array($locale, self::$allowed['locales'])) {
                    throw new \InvalidArgumentException(sprintf(
                        'Locale "%s" does not exist. Available locales are: "%s".',
                        $locale,
                        implode('", "', array_values(self::$allowed['locales']))
                    ));
                }
            }
        }
        $baseUrl = $this->getMinkParameter('base_url');
        $baseUrl = parse_url($baseUrl, PHP_URL_SCHEME).'://'.parse_url($baseUrl, PHP_URL_HOST);
        if (self::$jdr) {
            $baseUrl .= '/app_jdr.php';
        }
        if (!empty($locale)) {
            $baseUrl .= '/'.$locale;
        }
        $this->setMinkParameter('base_url', $baseUrl);
    }

    /**
     * Using a specific screen size
     *
     * @param $screenSize
     *
     * @Given /^(?:|I am )on a "(?P<screen_size>(?:[^"])+)" screen$/
     */
    public function onAScreenSize($screenSize)
    {
        if (!array_key_exists($screenSize, self::$allowed['screen_sizes'])) {
            throw new \InvalidArgumentException(sprintf(
                'Screen size "%s" does not exist. Available sizes are: "%s".',
                $screenSize,
                implode('", "', array_keys(self::$allowed['screen_sizes']))
            ));
        }
        $screenResolution = self::$allowed['screen_sizes'][$screenSize];
        try {
            $this->getSession()->resizeWindow($screenResolution['width'], $screenResolution['height'], 'current');
        } catch (UnsupportedDriverActionException $e) {
        }
    }

    /**
     * Checks, that element with specified CSS don't exist on page.
     *
     * @Then /^(?:|The )"(?P<element>[^"]*)" element is not set$/
     */
    public function assertElementIsNotSet($element)
    {
        $this->assertSession()->elementNotExists('css', $element);
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
     * Checks, that element with specified CSS is not empty.
     *
     * @Then /^(?:|The )"(?P<element>[^"]*)" element (should not be|is not) empty$/
     */
    public function assertElementNotEmpty($element)
    {
        $html = $this->assertSession()->elementExists('css', $element)->getHtml();
        if (empty($html)) {
            throw new \Exception('Element "'.$element.'" empty.');
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
     * @Then I should not see more than :count :element element
     */
    public function iShouldNotSeeMoreThanElement($count, $element)
    {
        $elements = $this->getSession()->getPage()->findAll('css', $element);
        return (count($elements) <= $count);
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
     * Redirection to an external site which host is <host>
     *
     * @Then /^(?:|I am )redirected to external site "(?P<host>[^"]*)"$/
     */
    public function redirectedToExternalSite($host)
    {
        $this->assertHostAddressEquals($host);
    }

    /**
     * Checks that current session host address is equals to provided one.
     *
     * @param $host only host (ex.: www.canaltp.fr)
     *
     * @throws ExpectationException
     */
    public function assertHostAddressEquals($host)
    {
        $url = parse_url($this->getSession()->getCurrentUrl());

        if ($url['host'] !== $host) {
            throw new ExpectationException(sprintf('Current host address is "%s", but "%s" expected.', $url['host'], $host), $this->getSession());
        }
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
     * Autocomplete field filler
     *
     * @When /^I fill in "(?P<field>[^"]*)" with autocomplete "(?P<value>[^"]*)"(?:| in "(?P<form>[^"]*)" form)$/
     */
    public function iFillWithAutocomplete($field, $value, $form = null)
    {
        $fieldId = ($form !== null ? '#ctp-'.$form.'Form ' : '').'#'.$field;
        $this->getSession()->executeScript('CanalTP.jQuery("'.$fieldId.'").val("'.$value.'");');
        $this->getSession()->executeScript('CanalTP.jQuery("'.$fieldId.'").autocomplete("search");');
        $target = $this->assertSession()->elementExists('css', $fieldId)->getAttribute('data-target-list');
        $this->waitFor(
            $this->timeouts['autocomplete'] / 1000,
            function ($context, $parameters) {
                return $context->assertSession()->elementExists('css', '#'.$parameters['target'])->isVisible();
            },
            array('target' => $target)
        );
        $this->clickOn('#'.$target.' .ui-autocomplete-item-0 a');
    }

    /**
     * Linked fields by AJAX filler
     *
     * @When /^I fill in "(?P<field>[^"]*)" with option "(?P<value>[^"]*)"(?:| waiting for "(?P<target>[^"]*)")$/
     */
    public function iFillWithOption($field, $value, $target = null)
    {
        $this->getSession()->executeScript(
            'CanalTP.jQuery("#'.$field.' option").each(function() {
                if (CanalTP.jQuery.trim(this.value) === "'.trim($value).'" || CanalTP.jQuery.trim(this.text) === "'.trim($value).'") {
                    CanalTP.jQuery("#'.$field.'").val(this.value).change();
                }
            });'
        );
        if ($target !== null) {
            $this->waitFor(
                $this->timeouts['ajax'] / 1000,
                function ($context, $parameters) {
                    try {
                        $target = $context->assertSession()->elementExists('css', '#'.$parameters['target']);

                        return $target->isVisible();
                    } catch (\Exception $e) {
                    }
                },
                array('target' => $target)
            );
        }
    }

    /**
     * click on css element
     *
     * @When /^(?:|I) click on "(?P<element>[^"]*)"$/
     */
    public function clickOn($element)
    {
        $node = $this->assertSession()->elementExists("css", $element);
        if ($node->hasAttribute('target') && $node->getAttribute('target') !== '_self') {
            $path = $node->getAttribute('href');
            $this->visitPath($path);
        } else {
            $node->click();
        }
    }

    /**
     * Form submit function
     *
     * @When /^I submit the "(?P<form>[^"]*)" form$/
     */
    public function iSubmitTheForm($form)
    {
        $this->clickOn('#ctp-'.$form.'Form [type="submit"]');
    }

    /**
     * Accordion toggle function
     *
     * @When /^I click on "(?P<block>[^"]*)" accordion$/
     */
    public function iClickOnAccordion($block)
    {
        $accordionToggle = $this->assertSession()->elementExists('css', $block.' .accordion-toggle');
        $accordionTarget = $accordionToggle->getAttribute('data-target');
        if (!$this->assertSession()->elementExists('css', $accordionTarget)->isVisible()) {
            $accordionToggle->click();
        }
    }

    /**
     * Wait until function, execute a callback every 50ms
     *
     * @param integer $timeout the timeout expressed in ms
     * @param callable $callback the lambda to execute
     * @param array $parameters the parameters to use for the lambda
     *
     * @return bool
     *
     * @throws \Exception If the execution time exceed the given timeout
     */
    protected function waitFor($timeout, $callback, $parameters = array())
    {
        for ($i = 0; $i < $timeout; $i += $elapsedTime) {
            $startTime = microtime(true);
            try {
                if ($callback($this, $parameters)) {
                    return true;
                }
            } catch (Exception $e) {
            }
            // sleeps for 50ms
            usleep(50000);
            $elapsedTime = microtime(true) - $startTime;
        }

        throw new \Exception('Timeout during the step');
    }

    /**
     * @When /^I have a cookie "(?P<name>[^"]*)" during "(?P<duration>[^"]*)"$/
     */
    protected function assertCookieDuration($name, $duration)
    {
        usleep(100 * 1000); // wait 100ms to avoid reading the cookie before it is saved to the session
        $datetime = date('d/m/Y', $this->getSession()->getCookie($name));
        $expectedDatetime = date('d/m/Y', strtotime('+'.$duration));

        if ($datetime !== $expectedDatetime) {
            throw new \Exception('The cookie expiration date ('.$datetime.') is different than expected ('.$expectedDatetime.').');
        }
    }

    /**
     * @Then The :element element is the last child
     */
    public function theElementIsTheLastChild($element)
    {
        $el = $this->getSession()->getPage()->find('css', $element.':last-child');

        if (!($el instanceof NodeElement)) {
            throw new ExpectationException('Element '.$element.' is not at last position', $this->getSession());
        }
    }

    /**
     * @Then The :element1 element is placed after :element2 element
     */
    public function theElementIsPlacedAfterElement($element1, $element2)
    {
        $el = $this->getSession()->getPage()->find('css', $element2.' + '.$element1);

        if (!($el instanceof NodeElement)) {
            throw new ExpectationException('Element '.$element1.' is not placed after '.$element2, $this->getSession());
        }
    }

    /**
     * Selects option in facultative select field with specified id|name|label|value.
     *
     * @When /^(?:|I )select "(?P<option>(?:[^"]|\\")*)" from facultative "(?P<select>(?:[^"]|\\")*)"$/
     */
    public function selectFacultativeOption($select, $option)
    {
        if ($option !== 'default') {
            parent::selectOption($select, $option);
        }
    }

    /**
     * @Then the following elements should be on the page:
     */
    public function elementsOnPage(TableNode $table)
    {
        foreach ($table->getRowsHash() as $field => $value) {
            $this->assertElementOnPage($field);
        }
    }

    /**
     * @Then the following are visible:
     */
    public function fieldsAreVisible(TableNode $table)
    {
        foreach ($table->getRowsHash() as $field => $value) {
            $this->assertElementVisible($field);
        }
    }

    /**
     * @Then the following have errors:
     */
    public function fieldsHaveErrors(TableNode $table)
    {
        foreach ($table->getRowsHash() as $field => $value) {
            $this->assertSession()->elementExists('css', '.has-error '.$field);
        }
    }

    /**
     * @Then the following have not errors:
     */
    public function fieldsHaveNotErrors(TableNode $table)
    {
        foreach ($table->getRowsHash() as $field => $value) {
            // check if field is not into .field-container.error
            $this->assertElementNotOnPage($field.'.error');
        }
    }

    /**
     * Then (?:|I) fill "(?<field>)" with date "(?<dateFormat>)"
     * @Then I fill :field with date :date
     *
     * @param $field
     * @param $dateFormat
     */
    public function iFillWithDate($field, $dateFormat)
    {
        $date = new \DateTime($dateFormat);
        $this->getSession()->getPage()->find('css', $field)->setValue($date->format('d/m/Y'));
    }

    /**
     * {@inheritdoc}
     */
    public function assertCheckboxChecked($checkbox)
    {
        try {
            parent::assertCheckboxChecked($checkbox);
        } catch (ElementNotFoundException $e) {
            if (!$this->assertSession()->elementExists('css', $checkbox)->isChecked()) {
                throw new ExpectationException(sprintf('Checkbox "%s" is not checked, but it should be.', $checkbox), $this->getSession()->getDriver());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assertCheckboxNotChecked($checkbox)
    {
        try {
            parent::assertCheckboxNotChecked($checkbox);
        } catch (ElementNotFoundException $e) {
            if ($this->assertSession()->elementExists('css', $checkbox)->isChecked()) {
                throw new ExpectationException(sprintf('Checkbox "%s" is checked, but it should not be.', $checkbox), $this->getSession()->getDriver());
            }
        }
    }

    /**
     * @Then I am on the locale :locale
     */
    public function iAmOnTheLocale($locale)
    {
        $url = parse_url($this->getSession()->getCurrentUrl());
        $pathComponents = explode('/', $url['path']);
        if ($pathComponents[2] != $locale) {
            throw new ExpectationException(sprintf('Current locale is "%s", expecting "%s"', $pathComponents[2], $locale), $this->getSession()->getDriver());
        }
    }

    /**
     * @return array all the clients given in configuration
     */
    private function getClients()
    {
        $clients = array();
        foreach (self::$allowed['clients'] as $key => $values) {
            if (is_array($values)) {
                $clients = array_merge($clients, $values);
            } else {
                $clients[] = $values;
            }
        }

        return $clients;
    }

    /**
     * Get the design for the given client
     *
     * @param string $client
     * @return string
     */
    private function getDesign($client)
    {
        foreach (self::$allowed['clients'] as $design => $clients) {
            if (is_array($clients) && in_array($client, $clients)) {
                return $design;
            }
        }

        return '';
    }
}
