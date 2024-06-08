<?php

namespace Wgroup\CustomerUnsafeAct;

use DB;
use Exception;
use Log;
use Str;

class CustomerUnsafeActService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
    }

    public function init()
    {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param int $customerId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerId = 0)
    {

        $model = new CustomerUnsafeAct();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerUnsafeActRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_unsafe_act.id',
            'wg_customer_unsafe_act.dateOf',
            'wg_customer_config_workplace.name',
            'customer_unsafe_act_risk_type.item',
            'wg_customer_unsafe_act.place',
            'wg_customer_unsafe_act.description',
            'customer_unsafe_act_status.item'
        ];

        $i = 0;

        foreach ($sorting as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_customer_unsafe_act.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_unsafe_act.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_unsafe_act.dateOf', $search);
            $filters[] = array('wg_customer_unsafe_act.description', $search);
            $filters[] = array('wg_customer_unsafe_act.place', $search);
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_config_job_activity_hazard_classification.name', $search);
            $filters[] = array('customer_unsafe_act_status.item', $search);
        }

        $this->repository->setColumns(['wg_customer_unsafe_act.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerUnsafeAct();
        $this->repository = new CustomerUnsafeActRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_unsafe_act.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_unsafe_act.dateOf', $search);
            $filters[] = array('wg_customer_unsafe_act.description', $search);
            $filters[] = array('wg_customer_unsafe_act.place', $search);
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_config_job_activity_hazard_classification.name', $search);
            $filters[] = array('customer_unsafe_act_status.item', $search);
        }

        $this->repository->setColumns(['wg_customer_unsafe_act.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }


    public function getYearFilter($customerId)
    {

        $query = "SELECT
	DISTINCT 0 id, YEAR(o.dateOf) item, YEAR(o.dateOf) `value`
FROM
	wg_customer_unsafe_act o
WHERE customer_id = :customer_id
ORDER BY YEAR(o.dateOf) DESC
";
        $results = DB::select($query, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getDashboardPeriodLine($customerId, $year, $workPlace)
    {
        $andWhere = $workPlace && trim($workPlace) != "" ? " AND o.work_place = '$workPlace' " : "";

        $sql = "select 'Eventos' label
	, SUM(case when MONTH(o.dateOf) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.dateOf) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.dateOf) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.dateOf) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.dateOf) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.dateOf) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.dateOf) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.dateOf) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.dateOf) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.dateOf) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.dateOf) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.dateOf) = 12 then 1 end) DIC
from
	wg_customer_unsafe_act o
where customer_id = :customer_id and YEAR(o.dateOf) = :year $andWhere";

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
            'year' => $year,
        ));

        if (!empty($results)) {

            $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

            $lineChartAvgDataSet = array();

            foreach ($results as $line) {

                $lineChartAvgDataSet[] = array(
                    "label" => $line->label,
                    "fillColor" => $this->hex2rgba("#DA4F4A", 0.2),
                    "strokeColor" => $this->hex2rgba("#DA4F4A", 1),
                    "pointColor" => $this->hex2rgba("#DA4F4A", 1),
                    "pointStrokeColor" => '#fff',
                    "pointHighlightFill" => '#fff',
                    "pointHighlightStroke" => $this->hex2rgba("#DA4F4A", 1),
                    "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                );
            }

            $lineChartAvg = array();

            $lineChartAvg["labels"] = $label;
            $lineChartAvg["datasets"] = $lineChartAvgDataSet;
        } else {
            $lineChartAvg = null;
        }

        return $lineChartAvg;
    }

    public function getDashboardWorkplaceLine($customerId, $year, $month, $workPlace)
    {
        $andWhere = $month && trim($month) != "" ? " AND MONTH(c.dateOf) = '$month' " : "";
        $andWhere .= $workPlace && trim($workPlace) != "" ? " AND c.work_place = '$workPlace' " : "";

        $sql = "SELECT
		cc.name AS label,
		COUNT(*) `total`
	FROM
		wg_customer_unsafe_act c
	INNER JOIN wg_customer_config_workplace cc ON c.work_place = cc.id
where c.customer_id = :customer_id and YEAR(c.dateOf) = :year $andWhere
GROUP BY cc.id";

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
            'year' => $year,
        ));

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => array()
        );

        $config["valueColumns"][] = array(
            "field" => 'total',
            "label" => 'Cantidad'
        );

        return $this->getChartBar($results, $config);
    }

    public function getDashboardRiskTypeLine($customerId, $year, $month, $workPlace)
    {
        $andWhere = $month && trim($month) != "" ? " AND MONTH(c.dateOf) = '$month' " : "";
        $andWhere .= $workPlace && trim($workPlace) != "" ? " AND c.work_place = '$workPlace' " : "";

        $sql = "SELECT
		wg_config_job_activity_hazard_classification.name AS label,
		COUNT(*) `total`
	FROM
		wg_customer_unsafe_act c
	LEFT JOIN wg_config_job_activity_hazard_classification
			ON c.risk_type = wg_config_job_activity_hazard_classification.`id`
where c.customer_id = :customer_id and YEAR(c.dateOf) = :year $andWhere
GROUP BY c.risk_type";

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
            'year' => $year,
        ));

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => array()
        );

        $config["valueColumns"][] = array(
            "field" => 'total',
            "label" => 'Cantidad'
        );

        return $this->getChartBar($results, $config);
    }

    public function getDashboardStatusLine($customerId, $year, $month, $workPlace)
    {
        $andWhere = $month && trim($month) != "" ? " AND MONTH(c.dateOf) = '$month' " : "";
        $andWhere .= $workPlace && trim($workPlace) != "" ? " AND c.work_place = '$workPlace' " : "";

        $sql = "SELECT
		aden_partner.item AS label,
		COUNT(*) `total`
	FROM
		wg_customer_unsafe_act c
	LEFT JOIN(SELECT `id`, `namespace`, `group`, `item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters` WHERE `namespace` = 'wgroup' AND `group` = 'customer_unsafe_act_status') aden_partner
			ON c.status = aden_partner.`value`
where c.customer_id = :customer_id and YEAR(c.dateOf) = :year
GROUP BY c.status";

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
            'year' => $year,
        ));

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => array()
        );

        $config["valueColumns"][] = array(
            "field" => 'total',
            "label" => 'Cantidad'
        );

        return $this->getChartBar($results, $config);
    }


    private $colors = ['#3395FF', '#e0d653', '#F7464A', '#5cb855', '#5AD3D1', '#ff6d00', '#3e2723', '#ff4081'];

    //--------------------------------------------------------------[PUBLIC METHODS]

    public function getChartPie($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $index = 0;
        foreach ($data as $pie) {
            $pie->color = $this->colors[$index];
            $pie->highlightColor = $this->colors[$index];
            $pie->value = (float) $pie->{"value"};
            $index++;
        }

        return $data;
    }

    public function getChartBar($data, $config)
    {
        if (!is_array($data)) {
            return null;
        }

        if (!is_array($config)) {
            return null;
        }

        $labelColumn = $config['labelColumn'];

        $valueColumns = $config['valueColumns'];

        $chart = [
            "labels" => $this->getLabel($data, $labelColumn),
            "datasets" => $this->getDataSetChart($data, $valueColumns)
        ];

        return $chart;
    }

    public function getDataSetChart($data, $valueColumns)
    {
        $dataSet = [];
        $dataSetItem = [];

        foreach ($data as $row) {
            foreach ($valueColumns as $key => $column) {
                $field = $column['field'];
                $dataSetItem[$column['label']][] = floatval($row->{"$field"});
            }
        }

        $index = 0;
        foreach ($dataSetItem as $label => $values) {
            $dataSet[] = $this->getDataSetItemChart($label, $this->colors[$index], $values);
            $index++;
        }

        return $dataSet;
    }

    public function getDataSetItemChart($label, $color, $data)
    {
        return [
            "label" => $label,
            "fillColor" => $this->hex2rgba($color, 1),
            "strokeColor" => $this->hex2rgba($color, 0.2),
            "pointColor" => $this->hex2rgba($color, 1),
            "pointStrokeColor" => '#fff',
            "pointHighlightFill" => '#fff',
            "pointHighlightStroke" => $this->hex2rgba($color, 1),
            "data" => $data,
        ];
    }


    //--------------------------------------------------------------[PRIVATE METHODS]

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

    private function getRandomColor()
    {
        return RandomColor::one(array(
            'luminosity' => 'bright',
            'hue' => 'green',  // red, orange, yellow, green, blue, purple, pink, monochrome
            'format' => 'rgb' // e.g. 'rgb(225,200,20)'
        ));
    }
}
