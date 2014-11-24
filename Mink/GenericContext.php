<?php

namespace CanalTP\NmpAcceptanceTestBundle\Mink;

use Behat\MinkExtension\Context\MinkContext;

class GenericContext extends MinkContext
{        
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
    public function forTheCustomer($client, $server = '', $locale = '')
    {
        $clients = array('Amiens','Breizhgo','Ctp','Destineo','Jvmalin','Plugnplay','Star','Vitici');           // A dynamiser
        $servers = array('local','dev','internal','customer');                                                  // A dynamiser
        $locales = array('fr','en','nl','br','de');                                                             // A dynamiser
        if (!in_array($client, $clients)) {
            throw new \Exception('Customer "'.$client.'" undefined.');
        }
        if (empty($server)) {
            $server = 'local';
        }
        if (!in_array($server, $servers)) {
            throw new \Exception('Server "'.$server.'" undefined.');
        }
        if (empty($locale)) {
            $locale = 'fr';
        }
        if (!in_array($locale, $locales)) {
            throw new \Exception('Locale "'.$locale.'" undefined.');
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
}