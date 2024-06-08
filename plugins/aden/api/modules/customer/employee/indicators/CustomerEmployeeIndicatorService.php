<?php

namespace AdeN\Api\Modules\Customer\Employee\Indicators;

use Illuminate\Support\Facades\DB;

use AdeN\Api\Classes\BaseService;
use Wgroup\SystemParameter\SystemParameter;

class CustomerEmployeeIndicatorService extends BaseService
{
    private $customerId;

    private $year;

    private $workplace;


    public  function setCustomerId($customerId){
        $this->customerId = $customerId;
    }

    public  function setYear($year){
        $this->year = $year;
    }

    public  function setWorkplace($workplace){
        $this->workplace = $workplace;
    }



    public function getTotalEmployees() {
        return $this->getBasicQueryGroupByMonths()
            ->orderBy(DB::raw("MONTH(period)"), 'DESC')
            ->limit('1')
            ->sum('total');
    }


    public function getActiveAndInactiveEmployeeChartPie()
    {
        $data = $this->getBasicQueryGroupByMonths()
            ->orderBy(DB::raw("MONTH(period)"), 'DESC')
            ->select(
                DB::raw('sum(total) AS total'),
                DB::raw('sum(count_actives) AS active'),
                DB::raw('sum(count_inactives) AS inactive'),
                DB::raw('coalesce(round(sum(count_actives) / sum(total) * 100, 2), 0) AS percentActive'),
                DB::raw('coalesce(round(sum(count_inactives) / sum(total) * 100, 2), 0) AS percentInactive')
            )
            ->first();

        if (empty($data)) {
            return [];
        }

        $chart = [
            [
                "label" => "{$data->percentActive}% Activos",
                "value" => $data->active,
                "color" => '#68bc47'
            ],
            [
                "label" => "{$data->percentInactive}% Inactivos",
                "value" => $data->inactive,
                "color" => '#6f8896'
            ]
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }


    public function getAuthorizedEmployeeChartPie()
    {
        $data = $this->getBasicQueryGroupByMonths()
            ->orderBy(DB::raw("MONTH(period)"), 'DESC')
            ->select(
                DB::raw('sum(count_actives) AS totalActive'),
                DB::raw('sum(count_autorized) AS authorized'),
                DB::raw('sum(count_not_autorized) AS unauthorized'),
                DB::raw('coalesce(round(sum(count_autorized) / sum(count_actives) * 100, 2), 0) AS percentAuthorized'),
                DB::raw('coalesce(round(sum(count_not_autorized) / sum(count_actives) * 100, 2), 0) AS percentUnauthorized')
            )
            ->first();

        if (empty($data)) {
            return [];
        }

        $chart = [
            [
                "label" => "{$data->percentAuthorized}% Autorizados",
                "value" => $data->authorized,
                "color" => '#68bc47'
            ],
            [
                "label" => "{$data->percentUnauthorized}% No autorizados",
                "value" => $data->unauthorized,
                "color" => '#6f8896'
            ]
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }



    public function getCharLineEmployeesByWorkplaces() {
        $subquery = $this->getBaseQuery()
            ->select(
                DB::raw("'Total' AS label"),
                DB::raw("CASE WHEN wp.name IS NULL THEN 'Sin definir' ELSE wp.name END AS dynamicColumn"),
                DB::raw("sum(c.total) AS total")
            );

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartBarAmountActiveEmployees()
    {
        $data = $this->getBaseQuery()
            ->select(
                DB::raw("CASE WHEN wp.name IS NULL THEN 'Sin definir' ELSE wp.name END AS label"),
                DB::raw("sum(c.count_actives) AS countActives"),
                DB::raw("sum(c.count_inactives) AS countInactives")
            )
            ->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Activos', 'field' => 'countActives', 'color' => '#22b14c' ],
                ['label' => 'Inactivos', 'field' => 'countInactives', 'color' => '#cb3434' ],
            ],
        );

        return $this->chart->getChartBar($data, $config);
    }


    public function getChartBarAmountAutorizedEmployees()
    {
        $data = $this->getBaseQuery()
            ->select(
                DB::raw("CASE WHEN wp.name IS NULL THEN 'Sin definir' ELSE wp.name END AS label"),
                DB::raw("sum(c.count_autorized) AS countAutorized"),
                DB::raw("sum(c.count_not_autorized) AS countNotAutorized")
            )
            ->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Autorizados',    'field' => 'countAutorized',    'color' => '#22b14c' ],
                ['label' => 'No Autorizados', 'field' => 'countNotAutorized', 'color' => '#cb3434' ],
            ],
        );

        return $this->chart->getChartBar($data, $config);
    }


    public function getBaseQuery() {
        $workplace = $this->workplace;

        return DB::table('wg_customer_employee_status_consolidate as c')
            ->leftJoin('wg_customer_config_workplace as wp', function($join) {
                $join->on('wp.customer_id', 'c.customer_id');
                $join->on('wp.id', 'c.workplace_id');
            })
            ->leftJoin('wg_customer_employee_status_consolidate as c2', function($join) {
                $join->on('c2.customer_id', 'c.customer_id');
                $join->on('c.period', '<', 'c2.period');
            })
            ->where('c.customer_id', $this->customerId)
            ->whereYear('c.period', $this->year)
            ->whereNull('c2.id')
            ->when($workplace, function($query) use ($workplace) {
                $query->where('c.workplace_id', $workplace);
            })
            ->groupBy('c.workplace_id')
            ->orderBy('wp.name');
    }


    private function getBasicQuery() {
        $workplace = $this->workplace;

        return DB::table('wg_customer_employee_status_consolidate')
            ->where('customer_id', $this->customerId)
            ->whereYear('period', $this->year)
            ->when($workplace, function($query) use ($workplace) {
                $query->where('workplace_id', $workplace);
            });
    }


    private function getBasicQueryGroupByMonths() {
        return $this->getBasicQuery()
            ->groupBy(DB::raw('month(period)'))
            ->select(
                DB::raw("month(period) AS month")
            );
    }


    public function getChartLineAmountEmployeesByPeriod() {
        $subquery = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'Total' AS label"))
            ->addSelect(DB::raw('sum(total) as value'));

        $data = $this->applyTextToMonths($subquery)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }


    public function getChartLineAmountEmployeesVsActiveVsInactiveByPeriod()
    {
        $queryTotal = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'Total' AS label"))
            ->addSelect(DB::raw('sum(total) as value'));

        $queryActive = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'Activos' AS label"))
            ->addSelect(DB::raw('sum(count_actives) as value'));

        $queryInactive = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'Inactivos' AS label"))
            ->addSelect(DB::raw('sum(count_inactives) as value'));

        $subquery = $queryTotal
            ->union($queryActive)->mergeBindings($queryActive)
            ->union($queryInactive)->mergeBindings($queryInactive);

        $data = $this->applyTextToMonths($subquery)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }


    public function getChartLineAmountActiveVsAutorizedVsUnautorizedByPeriod()
    {
        $queryActive = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'Activos' AS label"))
            ->addSelect(DB::raw('sum(count_actives) as value'));

        $queryAutorized = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'Autorizados' AS label"))
            ->addSelect(DB::raw('sum(count_autorized) as value'));

        $queryUnautorized = $this->getBasicQueryGroupByMonths()
            ->addSelect( DB::raw("'No Autorizados' AS label"))
            ->addSelect(DB::raw('sum(count_not_autorized) as value'));

        $subquery = $queryAutorized
            ->union($queryActive)->mergeBindings($queryActive)
            ->union($queryUnautorized)->mergeBindings($queryUnautorized);

        $data = $this->applyTextToMonths($subquery)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }

}
