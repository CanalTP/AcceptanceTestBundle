<?php

namespace CanalTP\NmpAcceptanceTestBundle\Behat\MinkExtension\Context;

use Behat\MinkExtension\Context\MinkContext as BaseMinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Symfony\Component\HttpKernel\KernelInterface;

class MinkContext extends BaseMinkContext implements SnippetAcceptingContext
{
    /**
     * Behat additional options
     * @var array $options
     */
    public static $options;
    /**
     * Allowed values for addtional options
     * @var array $allowed
     */
    public static $allowed;
    /**
     * Application Kernel
     * @var KernelInterface $kernel
     */
    private $kernel;

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
                'locales' => $container->getParameter('behat.locales')
            );
        }
    }
    
    /**
     * Behat additional options initializer
     */
    public function __construct() {
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
    public function forTheClient($client, $server = '', $locale = '')
    {
        if (!in_array($client, self::$allowed['clients'])) {
            throw new \Exception('Website client "'.$client.'" not found.');
        }        
        if (!in_array($server, self::$allowed['servers']) && !empty($server)) {
            throw new \Exception('Website server "'.$server.'" not found.');
        } else {
            $server = self::$options['server'];
        }
        if (!in_array($locale, self::$allowed['locales']) && !empty($locale)) {
            throw new \Exception('Website locale "'.$locale.'" not found.');
        } else {
            $locale = self::$options['locale'];
        }
        $this->setMinkParameter('base_url', strtr('http://nmp-ihm.'.strtolower($client).'.'.strtolower($server).'.canaltp.fr/'.$locale, array(' ', '')));
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
     * @param type $element
     * @param type $children
     */
    public function assertElementChildrenOnPage($element, $children = array())
    {
        foreach ($children as $child) {
            $this->assertElementOnPage($element . ' ' . $child);
        }
    }
    
    /**
     * Checks, that element children with specified CSS are not on page.
     * 
     * @param type $element
     * @param type $children
     */
    public function assertElementChildrenNotOnPage($element, $children = array())
    {
        foreach ($children as $child) {
            $this->assertElementNotOnPage($element . ' ' . $child);
        }
    }
    
    /**
     * Checks, that element childrens with specified CSS are visible on page.
     */
    public function assertElementChildrensVisible($element, $childrens = array())
    {
        foreach ($childrens as $children) {
            $this->assertElementVisible($element.' '.$children);
        }
    }
    
    /**
     * Checks, that element childrens with specified CSS are not visible on page.
     */
    public function assertElementChildrensNotVisible($element, $childrens = array())
    {
        foreach ($childrens as $children) {
            $this->assertElementNotVisible($element.' '.$children);
        }
    }
    
    protected function getTestCaseUrl($name)
    {
        //$container = $this->getContainer();
        //echo $container->getParameter('test_cases');
    }
}