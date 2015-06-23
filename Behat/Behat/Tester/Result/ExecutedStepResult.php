<?php

namespace CanalTP\NmpAcceptanceTestBundle\Behat\Behat\Tester\Result;

use Behat\Behat\Definition\SearchResult;
use Behat\Testwork\Call\CallResult;
use Behat\Testwork\Tester\Result\ExceptionResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Behat\Tester\Result\DefinedStepResult;
use ReflectionClass;

/**
 * Represents an executed (successfully or not) step result.
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
final class ExecutedStepResult implements StepResult, DefinedStepResult, ExceptionResult
{
    /**
     * @var SearchResult
     */
    private $searchResult;
    /**
     * @var null|CallResult
     */
    private $callResult;

    /**
     * Initialize test result.
     *
     * @param SearchResult $searchResult
     * @param CallResult   $callResult
     */
    public function __construct(SearchResult $searchResult, CallResult $callResult)
    {
        $this->searchResult = $searchResult;
        $this->callResult = $callResult;
    }

    /**
     * Returns definition search result.
     *
     * @return SearchResult
     */
    public function getSearchResult()
    {
        return $this->searchResult;
    }

    /**
     * Returns definition call result or null if no call were made.
     *
     * @return CallResult
     */
    public function getCallResult()
    {
        return $this->callResult;
    }

    /**
     * {@inheritdoc}
     */
    public function getStepDefinition()
    {
        return $this->searchResult->getMatchedDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function hasException()
    {
        return null !== $this->getException();
    }

    /**
     * {@inheritdoc}
     */
    public function getException()
    {
        return $this->callResult->getException();
    }

    /**
     * {@inheritdoc}
     */
    public function getResultCode()
    {
        if ($this->callResult->hasException()) {
            $reflect = new ReflectionClass($this->callResult->getException());
            switch ($reflect->getShortName()) {
                case 'PendingException':
                    return self::PENDING;
                case 'SkippedException':
                    return self::SKIPPED;
            }
        }

        if ($this->callResult->hasException()) {
            return self::FAILED;
        }

        return self::PASSED;
    }

    /**
     * {@inheritdoc}
     */
    public function isPassed()
    {
        return self::PASSED == $this->getResultCode();
    }
}
