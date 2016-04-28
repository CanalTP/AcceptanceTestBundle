<?php

namespace CanalTP\AcceptanceTestBundle\Mock;

use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractServiceMock
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
