<?php

/*
 * This file is part of the Behat Gherkin.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CanalTP\NmpAcceptanceTestBundle\Behat\Gherkin;

use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser as BaseParser;

/**
 * Gherkin parser.
 *
 * $lexer  = new Behat\Gherkin\Lexer($keywords);
 * $parser = new CanalTP\NmpAcceptanceTest\Behat\Gherkin\Parser($lexer);
 * $featuresArray = $parser->parse('/path/to/feature.feature');
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 */
class Parser extends BaseParser
{
    /**
     * Test cases
     *
     * @var array $testCases
     */
    private $testCases = array();

    /**
     * Initializes parser.
     *
     * @param Lexer $lexer Lexer instance
     * @param array $testCases Test cases
     */
    public function __construct(Lexer $lexer, array $testCases = array())
    {
        parent::__construct($lexer);
        $this->testCases = $testCases;
    }

    /**
     * Parses examples table node.
     *
     * @return ExampleTableNode
     */
    protected function parseExamples()
    {
        $node = parent::parseExamples();
        if (empty($node->getTable())) {
            $module = trim($this->parseText());
            $node = new ExampleTableNode($this->loadExamplesFromTestCases($module), $node->getKeyword());
        }

        return $node;
    }

    /**
     * Examples loader from file
     *
     * @param string $keyword
     * @return array
     */
    private function loadExamplesFromTestCases($keyword)
    {
        $testCases = !empty($this->testCases[$keyword]) ? $this->testCases[$keyword] : array();
        $formattedTestCases = array();
        foreach ($testCases as $index => $testCase) {
            if (!$index) {
                $formattedTestCases[] = array_keys($testCase);
            }
            $formattedTestCases[] = array_values($testCase);
        }

        return $formattedTestCases;
    }
}
