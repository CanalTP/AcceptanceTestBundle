<?php

namespace CanalTP\AcceptanceTestBundle\Checker\Data\Html;

use Behat\Mink\Element\NodeElement;

class TableData
{
    protected $element;

    public function __construct(NodeElement $element)
    {
        $this->element = $element;
    }

    public function getColumn($columnId)
    {
        $columnId = 'col_first_name';

        $rows = $this->element->findAll('css', 'tr');

        // Check column number for this id
        $heads = $rows[0]->findAll('css', 'th');

        $columnNumber = null;
        foreach ($heads as $headNumber => $head) {
            $headId = $head->getAttribute('id');
            if ($headId == $columnId) {
                $columnNumber = $headNumber;
                break;
            }
        }

        // Get all cells for the column
        $cellData = array();
        foreach ($rows as $row) {
            $cells = $row->findAll('css', 'td');
            foreach ($cells as $cellNumber => $cell) {
                if ($cellNumber == $columnNumber) {
                    $cellData[] = $cell->getText();
                    break;
                }
            }
        }

        return $cellData;
    }
}