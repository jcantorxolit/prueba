<?php
/**
 * Created by PhpStorm.
 * User: David Blandon
 * Date: 4/25/2016
 * Time: 5:43 PM
 */

namespace AdeN\Api\Classes;


class ExcelSheet
{

    private $sheets = [];

    public function addSheet($name, $data, $columFormats = null)
    {
        $sheet = new \stdClass();
        $sheet->name = $name;
        $sheet->data = $data;
        $sheet->columFormats = $columFormats;

        $this->sheets[$name] = $sheet;

        return $this;
    }

    public function getSheets()
    {
        return $this->sheets;
    }

    public function getSheetAtIndex($index)
    {
        $copy = array_values($this->sheets);

        if ($index < count($copy)) {
            return $copy[$index];
        }

        return null;
    }

    public function getSheetByName($name)
    {
        return $this->sheets[$name];
    }

    public function clear()
    {
        $this->sheets = [];
        return $this;
    }
}