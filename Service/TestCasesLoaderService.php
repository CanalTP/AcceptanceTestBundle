<?php

namespace CanalTP\NmpAcceptanceTestBundle\Service;

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
    /**
     * {@inheritdoc}
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
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

            return !empty($yaml['canal_tp_nmp_acceptance_test']['test_cases']) ?
                $yaml['canal_tp_nmp_acceptance_test']['test_cases'] : array();
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
        return __DIR__.'/../Resources/config/test_cases/'.strtolower($client).'.yml';
    }
}
