<?php

namespace CanalTP\NmpAcceptanceTestBundle\Behat\MinkExtension\Context;

use Behat\MinkExtension\Context\MinkContext as BaseMinkContext;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Tester\Result\StepResult;

/**
 * Failed scenarios content tracer
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class TraceContext extends BaseMinkContext
{
    /**
     * Output path
     * 
     * @var string $outputPath
     */
    public static $outputPath = 'behat';
    /**
     * Output types
     * 
     * @var array $outputTypes
     */
    public static $outputTypes = array('html', 'png');
    /**
     * Scenario status
     * 
     * @var boolean $scenarioStatus
     */
    private $scenarioStatus;
    /**
     * Scenario files
     * 
     * @var array $files
     */
    private $files;
    /**
     * Step number
     * 
     * @var integer $stepNumber
     */
    private $stepNumber;
    /**
     * Step name
     * 
     * @var string $stepName
     */
    private $stepName;
    /**
     * Step visits
     * 
     * @var array $stepVisits
     */
    private $stepVisits;
    /**
     * Step visits number
     * 
     * @var integer $stepFilesNumber
     */
    private $stepVisitsNumber;
    
    /**
     * Get the path to write files
     * 
     * @param array $folders
     * @return string
     */
    private function getPath(array $folders)
    {
        $folders = array_merge(explode(DIRECTORY_SEPARATOR, self::$outputPath), $folders);
        $path = '';
        foreach ($folders as $folder) {
            if (strlen($folder)) {
                $path .= $this->getFormattedName($folder) . DIRECTORY_SEPARATOR;
            }
        }
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
    
    /**
     * Filename formatter
     * 
     * @param string $name
     * @return string
     */
    private function getFormattedName($name)
    {
        return preg_replace("#[^a-z0-9_\-\.]#", '-', strtolower($name));
    }
    
    /**
     * Page content (source, screenshot, etc)
     * 
     * @param string $stepName
     * @return array
     */
    private function getPageContent($stepName)
    {
        $stepFiles = array();
        $driver = $this->getSession()->getDriver();
        if ($driver instanceof Selenium2Driver) {
            $stepFiles = array();
            foreach (self::$outputTypes as $outputType) {
                switch ($outputType){
                    case 'png':
                        $stepFiles[$stepName.'.png'] = $driver->getScreenshot();
                        break;
                    case 'html':
                        $stepFiles[$stepName.'.html'] = $driver->getContent();
                        break;
                    default:
                        throw new \Exception('Output type "'.$outputType.'" not supported.');
                }
            }
        }
        return $stepFiles;
    }
    
    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $event)
    {
        $this->scenarioStatus = StepResult::PENDING;
        $this->files = array();
        $this->stepNumber = 0;
    }
    
    /**
     * @BeforeStep
     */
    public function beforeStep(BeforeStepScope $event)
    {
        $this->stepVisits = array();
        $this->stepVisitsNumber = 0;
        $this->stepName = $this->getFormattedName($event->getStep()->getText());
        $this->stepNumber++;
    }
    
    /**
     * Save content of the page visited
     * 
     * @param string $page
     */
    public function visit($page) {
        parent::visit($page);
        $stepName = $this->stepNumber.'.'.++$this->stepVisitsNumber.'_'.$this->stepName;
        $this->stepVisits = array_merge($this->stepVisits, $this->getPageContent($stepName));
    }
    
    /**
     * @AfterStep
     */
    public function afterStep(AfterStepScope $event)
    {
        if ($this->stepVisitsNumber > 1) {
            $this->files = array_merge($this->files, $this->stepVisits);
        } else {
            $stepName = $this->stepNumber.'_'.$this->stepName;
            $this->files = array_merge($this->files, $this->getPageContent($stepName));
        }
        if ($event->getTestResult()->getResultCode() == StepResult::FAILED) {
            $this->scenarioStatus = StepResult::FAILED;
        }
    }
     
    /**
     * @AfterScenario
     */
    public function afterScenario(AfterScenarioScope $event)
    {
        if ($this->scenarioStatus === StepResult::FAILED) {
            $scenarioTitle = $event->getScenario()->getTitle();
            $folders = array(
                $event->getSuite()->getName(),
                $event->getFeature()->getTitle().'.'.$scenarioTitle
            );
            $path = $this->getPath($folders);
            foreach ($this->files as $file => $content) {
                file_put_contents($path . $file, $content);
            }
            $nbFiles = count($this->files);
            print "Trace:\n"
                . "- Directory: {$path}\n"
                . "- Files: {$nbFiles}";
        }
    }
}
