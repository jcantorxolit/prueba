<?php

/**
 * Created by PhpStorm.
 * User: David Blandon
 * Date: 4/25/2016
 * Time: 5:43 PM
 */

namespace AdeN\Api\Classes;

use Illuminate\Support\Facades\Log;
use October\Rain\Support\Collection;

class Chart
{
    private $colors = [
        '#f59747', '#3877b4', '#e7e7e7',
        '#F50057', '#26A69A', '#D4E157', '#FFCE56', '#36A2EB', '#FF5722', '#FF6384', '#8BC34A', '#3395FF', '#E0D653',
        '#F7464A', '#5CB855', '#5AD3D1', '#FF6D00', '#3E2723', '#FF4081', '#2962FF', '#009688', '#D50000', '#673AB7',
        '#827717', '#FF6F00', '#A1887F', '#FFD600', '#78909C', '#C6FF00', '#880E4F', '#0D47A1', '#E65100', '#AED581',
        '#E57373', '#F06292', '#BA68C8', '#5E35B1', '#3949AB', '#1E88E5', '#01579B', '#006064', '#004D40', '#81C784',
        '#FFF9C4', '#FFECB3', '#FFE0B2', '#FF7043', '#8D6E63', '#BDBDBD', '#9FA8DA', '#536DFE', '#B3E5FC', '#9E9D24',
    ];

    private $months = [
        "label" => [
            "JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"
        ]
    ];

    private $monthsLabel = [
        "Enero",
        "Febrero",
        "Marzo",
        "Abril",
        "Mayo",
        "Junio",
        "Julio",
        "Agosto",
        "Septiembre",
        "Octubre",
        "Noviembre",
        "Diciembre"
    ];

    private $monthsAbbreviationLabel = [
        "ENE",
        "FEB",
        "MAR",
        "ABR",
        "MAY",
        "JUN",
        "JUL",
        "AGO",
        "SEP",
        "OCT",
        "NOV",
        "DIC"
    ];

    //--------------------------------------------------------------[PUBLIC METHODS]

    public function getChartPie($data)
    {
        $data = $data instanceof Collection ? $data->toArray() : $data;

        if (!is_array($data)) {
            return null;
        }

        $index = 0;

        $labels = [];
        $backgroundColor = [];
        $hoverBackgroundColor = [];
        $values = [];

        foreach ($data as $pie) {
            $color = $this->pickColor($index);
            if (property_exists($pie, 'color')) {
                $color = $pie->color;
            }

            $backgroundColor[] = $color;
            $hoverBackgroundColor[] = $color;
            $values[] = (float)$pie->{"value"};

            if (property_exists($pie, "label")) {
                $labels[] = $pie->{"label"};
            }
            $index++;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'backgroundColor' => $backgroundColor,
                'hoverBackgroundColor' => $hoverBackgroundColor,
                'data' => $values,
            ]]
        ];
    }

    public function getChartBar($data, $config)
    {
        return $this->getDataSeries($data, $config, 1);
    }

    public function getChartRadar($data, $config)
    {
        return $this->getDataSeries($data, $config, 0.5);
    }

    public function getChartLine($data, $config)
    {
        return $this->getDataSeries($data, $config);
    }

    private function getDataSeries($data, $config, $alpha = 0.5)
    {
        $data = $data instanceof Collection ? $data->toArray() : $data;

        if (!is_array($data)) {
            return null;
        }

        if (!is_array($config)) {
            return null;
        }

        $labelColumn = $config['labelColumn'];

        $valueColumns = $config['valueColumns'];

        $seriesLabel = array_key_exists('seriesLabel', $config) ? $config['seriesLabel'] : null;

        $chart = [
            "labels" => is_string($labelColumn) ? $this->getLabel($data, $labelColumn) : $labelColumn,
            "datasets" => $this->getDataSetChart($data, $valueColumns, $alpha, $seriesLabel)
        ];

        return $chart;
    }

    public function getDataSetChart($data, $valueColumns, $alpha = 0.5, $seriesLabel = null)
    {
        $dataSet = [];
        $dataSetItem = [];
        $colors = [];

        foreach ($data as $row) {
            foreach ($valueColumns as $key => $column) {
                if (isset($column['values'])) {
                    $labelField = $column['label'];
                    $values = $column['values'];

                    $label = $row->{"$labelField"};

                    foreach ($values as $val) {
                        $dataValue = $row->{"$val"};
                        $dataSetItem[$label][] = $dataValue ? floatval($dataValue) : 0;
                    }
                } else {
                    if (isset($column['labelField'])) {
                        $labelField = $column['labelField'];
                        $label = $row->{"$labelField"};
                    } else {
                        $label = $this->getSeriesLabel($column, $seriesLabel);
                        if ($label == null) {
                            continue;
                        }
                    }
                    $field = $column['field'];
                    $dataSetItem[$label][] = floatval($row->{"$field"});
                }

                if (isset($column['color'])) {
                    $colors[$label] = $column['color'];
                }
            }
        }

        $index = 0;
        foreach ($dataSetItem as $label => $values) {
            $color = count($colors) ? $colors[$label] : $this->pickColor($index);
            $dataSet[] = $this->getDataSetItemChart($label, $color, $values, $alpha);
            $index++;
        }

        return $dataSet;
    }

    public function getChartBarGroupedStack($data, $config, $stacks)
    {
        $labelColumn = $config['labelColumn'];
        $valueColumns = $config['valueColumns'];

        $datasets = [];

        $seriesData = [];

        $years = $data->pluck('label')->unique()->toArray();

        $added = [];
        foreach ($years as $year) {

            foreach ($stacks as $stack) {

                foreach ($valueColumns as $valueColumn) {
                    $label = $valueColumn['label'];
                    $column = $valueColumn['field'];

                    foreach ($data as $datum) {
                        $value = $datum->$column;

                        if ($year == $datum->label && $datum->stack == $stack) {
                            $added[$year][$stack][$label] = $datum;
                            $seriesData[$stack][$label][] = $value;
                        }
                    }
                }


                foreach ($valueColumns as $valueColumn) {
                    $label = $valueColumn['label'];

                    if (!isset($added[$year][$stack][$label])) {
                        $seriesData[$stack][$label][] = 0;
                    }
                }

            }
        }


        $index = 0;
        $added = [];
        foreach ($data as $datum) {
            foreach ($valueColumns as $valueColumn) {
                $stack = $datum->stack;
                $label = $valueColumn['label'];
                $color = $valueColumn['color'] ?? $this->pickColor($index);

                if (isset($added[$stack][$label])) {
                    continue;
                } else {
                    $added[$stack][$label] = true;
                }

                $value = $seriesData[$stack][$label];
                $datasets[] = $this->getDataSetItemChartWithStack($label, $color, $value, null, $stack);

                $index++;
            }
        }

        return [
            "labels" => is_string($labelColumn) ? $this->getLabel($data, $labelColumn) : $labelColumn,
            "datasets" => $datasets
        ];
    }


    public function getChartBarGroupedStack2($data, $config, $stacks)
    {
        $labelColumn = $config['labelColumn'];
        $valueColumns = $config['valueColumns'];

        $index = 0;
        $added = [];
        $seriesData = [];

        foreach ($valueColumns as $valueColumn) {
            $dataset = new \stdClass();
            $dataset->label = $valueColumn['label'];
            $dataset->color = $valueColumn['color'] ?? $this->pickColor($index);
            $dataset->stack = $valueColumn['stack'];

            $dataset->data = [];
            foreach ($data as $datum) {
                $column = $valueColumn['field'];

                if (empty($added[$dataset->label][$datum->label])) {
                    $dataset->data[] = $datum->$column;
                    $added[$dataset->label][$datum->label] = true;
                }
            }

            $index++;
            $seriesData[] = $dataset;
        }


        $datasets = [];
        foreach ($seriesData as $seriesDatum) {
            $datasets[] = $this->getDataSetItemChartWithStack(
                $seriesDatum->label,
                $seriesDatum->color,
                $seriesDatum->data,
                null,
                $seriesDatum->stack
            );
        }

        return [
            "labels" => is_string($labelColumn) ? $this->getLabel($data, $labelColumn) : $labelColumn,
            "datasets" => $datasets
        ];
    }


    private function getSeriesLabel($column, $seriesLabel)
    {
        if (!array_key_exists('code', $column) || $seriesLabel == null) {
            return $column['label'];
        }

        foreach ($seriesLabel as $serie) {
            if ($serie->code == $column['code']) {
                return $serie->text;
            }
        }

        return null;
    }

    public function getDataSetItemChart($label, $color, $data, $alpha = 0.5)
    {
        return [
            "label" => $label,
            "backgroundColor" => $this->hex2rgba($color, $alpha),
            "strokeColor" => $this->hex2rgba($color, $alpha),
            "pointBackgroundColor" => $this->hex2rgba($color, 1),
            "pointStrokeColor" => '#fff',
            "pointHighlightFill" => '#fff',
            "pointHighlightStroke" => $this->hex2rgba($color, 1),
            "data" => $data,
        ];
    }

    public function getDataSetItemChartWithStack($label, $color, $data, $alpha = 0.5, $stack = null)
    {
        return [
            "label" => $label,
            "backgroundColor" => $this->hex2rgba($color, $alpha),
            "stack" => $stack,
            "strokeColor" => $this->hex2rgba($color, $alpha),
            "pointBackgroundColor" => $this->hex2rgba($color, 1),
            "pointStrokeColor" => '#fff',
            "pointHighlightFill" => '#fff',
            "pointHighlightStroke" => $this->hex2rgba($color, 1),
            "data" => $data,
        ];
    }

    public function getMonthLabels()
    {
        return $this->monthsLabel;
    }

    public function getMonthColumnValueSeries()
    {
        return $this->getColumnValueSeriesV2($this->months);
    }

    //--------------------------------------------------------------[PRIVATE METHODS]

    private function getColumnValueSeries($columnValues)
    {
        $result = [];

        foreach ($columnValues as $key => $value) {
            $result[] = [
                "field" => $key,
                "label" => $value
            ];
        }

        return $result;
    }

    private function getColumnValueSeriesV2($columnValues)
    {
        $result = [];

        foreach ($columnValues as $key => $value) {
            $result[] = [
                "label" => $key,
                "values" => $value
            ];
        }

        return $result;
    }

    private function getLabel($data, $labelColumn)
    {
        $label = [];

        foreach ($data as $row) {
            $label[] = $row->{"$labelColumn"};
        }

        return $label;
    }

    private function hex2rgba($color, $opacity = false)
    {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color))
            return $default;

        //Sanitize $color if "#" is provided
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
    }

    private function pickColor($index)
    {
        if (array_key_exists($index, $this->colors)) {
            return $this->colors[$index];
        }

        return $this->generateRandomHexColor();
    }

    private function generateRandomHexColor()
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }
}
