<?php

namespace CanalTP\AcceptanceTestBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

/**
 * Test cases loader service
 *
 * @author Vincent Catillon <vincent.catillon@canaltp.fr>
 * @copyright Canal TP (c) 2015
 */
class TestCasesLoaderService extends ContainerAware
{
    private $testCasesPath;

    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container, $testCasesPath)
    {
        $this->setContainer($container);
        $this->testCasesPath = $testCasesPath;
    }

    /**
     * Test cases getter by client
     *
     * @param string $client
     * @return mixed
     */
    public function getTestCases($client)
    {
        $file = $this->getTestCasesFile($client);
        if (!file_exists($file) || !is_file($file)) {
            throw new NotFoundResourceException(sprintf('No test cases file found: %s', $file));
        } else {
            $parser = new Parser();
            $yaml = $parser->parse(file_get_contents($file));

            return !empty($yaml['canal_tp_acceptance_test']['test_cases']) ?
                $yaml['canal_tp_acceptance_test']['test_cases'] : array();
        }
    }

    /**
     * Test cases file getter by client
     *
     * @param string $client
     * @return string
     */
    private function getTestCasesFile($client)
    {
        return $this->testCasesPath.'/'.strtolower($client).'.yml';
    }
}
