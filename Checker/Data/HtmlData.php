<?php

namespace CanalTP\AcceptanceTestBundle\Checker\Data;

use Behat\Mink\Element\NodeElement;
use CanalTP\AcceptanceTestBundle\Checker\Data\Html\TableData;

class HtmlData
{
    protected $element;

    public function __construct(NodeElement $element)
    {
        $this->element = $element;
    }

    public function getTableData()
    {
        return new TableData($this->element);
    }
}