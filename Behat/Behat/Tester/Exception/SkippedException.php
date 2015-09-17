<?php

namespace CanalTP\AcceptanceTestBundle\Behat\Behat\Tester\Exception;

use Behat\Testwork\Tester\Exception\TesterException;
use RuntimeException;

/**
 * Represents a skipped exception.
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
final class SkippedException extends RuntimeException implements TesterException
{
    /**
     * Initializes pending exception.
     *
     * @param string $text
     */
    public function __construct($text = 'INFORMATION: skipped definition')
    {
        parent::__construct($text);
    }
}
