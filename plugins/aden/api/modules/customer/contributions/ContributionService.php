<?php

namespace AdeN\Api\Modules\Customer\Contributions;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Wgroup\SystemParameter\SystemParameter;

class ContributionService extends BaseService
{

    public function getQueryBalance()
    {
        $subquerySales = $this->getQueryExecutedCosts();

        // individual calculate
        $subquery = DB::table('wg_customer_arl_contribution as con')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month')), function ($join) {
                $join->on('month.value', '=', 'con.month');
            })
            ->leftJoin(DB::raw("({$subquerySales->toSql()}) as sales"), function ($join) {
                $join->on('sales.customer_id', 'con.customer_id');
                $join->on('sales.year', 'con.year');
                $join->on('sales.month', 'con.month');
            })
            ->select(
                'con.year',
                'con.input',
                DB::raw("input * percent_reinvestment_arl / 100 AS reinvestmentARL"),
                DB::raw("(input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100 AS reinvestmentWG"),
                DB::raw("sales.sales AS sales"),
                DB::raw("con.customer_id")
            );

        // grouped
        return DB::table(DB::raw("({$subquery->toSql()}) as o"))
            ->mergeBindings($subquery)
            ->groupBy('o.customer_id', 'year')
            ->select(
                'o.customer_id as customerId',
                'year',
                DB::raw("sum(coalesce(input, 0)) as contributions"),
                DB::raw("sum(coalesce(reinvestmentARL, 0)) as commissions"),
                DB::raw("sum(coalesce(reinvestmentWG, 0)) as reinvesments"),
                DB::raw("sum(coalesce(sales, 0)) as sales"),
                DB::raw("sum(coalesce(reinvestmentWG, 0)) - sum(coalesce(sales, 0)) as balance")
            );
    }


    public function getQueryExecutedCosts()
    {
        return DB::table('wg_customer_project_costs as pc')
            ->join('wg_customer_project as p', 'p.id', '=', 'pc.project_id')
            ->whereRaw("pc.status = 'SS002' ")
            ->whereRaw("( p.type = 'Intm' OR
                         (p.type = 'SYL' and pc.concept = 'PCOS014') OR
                         (p.type = 'RV' AND pc.concept = 'C03')
                        )")
            ->groupBy('p.customer_id', DB::raw('year(p.deliveryDate)'), DB::raw('month(deliveryDate)'))
            ->select(
                'p.customer_id',
                DB::raw("year(deliveryDate) as year"),
                DB::raw("month(deliveryDate) as month"),
                DB::raw("sum(pc.total_price) as sales")
            );
    }

    public function getQuerySalesByMonth()
    {

        return DB::table('wg_customer_project as p')
            ->join('wg_customer_project_costs as pc', 'pc.project_id', '=', 'p.id')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month')), function ($join) {
                $join->whereRaw('MONTH(p.deliveryDate) = month.value');
            })->leftjoin(DB::raw(SystemParameter::getRelationTable('project_type', 'type')), function ($join) {
                $join->on('p.type', '=', 'type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('project_concepts', 'concept')), function ($join) {
                $join->on('pc.concept', '=', 'concept.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('project_classifications', 'clas')), function ($join) {
                $join->on('pc.classification', '=', 'clas.value');
            })
            ->whereRaw("pc.status = 'SS002' ")
            ->whereRaw("( p.type = 'Intm' OR
                         (p.type = 'SYL' AND pc.concept = 'PCOS014') OR
                         (p.type = 'RV'  AND pc.concept = 'C03')
                        )")
            ->select(
                'month.item as period',
                'type.item as type',
                'p.name as activity',
                'concept.item as concept',
                'clas.item as classification',
                'pc.total_price as total',
                DB::raw('YEAR(p.deliveryDate) as year'),
                DB::raw('MONTH(p.deliveryDate) as month'),
                'p.customer_id as customerId',
                'p.id as project_id'
            );
    }

    public function getSalesMonth(int $customerId, int $year)
    {
        return DB::table('wg_customer_project')
            ->join('wg_customer_project_costs as pc', function ($join) {
                $join->on('pc.project_id', '=', 'wg_customer_project.id');
                $join->where('pc.status', 'SS002');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month')), function ($join) {
                $join->whereRaw('MONTH(wg_customer_project.deliveryDate) = month.value');
            })
            ->where('customer_id', $customerId)
            ->whereYear('deliveryDate', $year)
            ->whereRaw("( wg_customer_project.type = 'Intm' OR
                         (wg_customer_project.type = 'SYL' AND pc.concept = 'PCOS014') OR
                         (wg_customer_project.type = 'RV'  AND pc.concept = 'C03')
                        )")
            ->orderBy('month.value')
            ->select('month.value', 'month.item')
            ->distinct()
            ->get();
    }

    public function getSalesTypesByMonth(int $customerId, int $year)
    {
        return DB::table('wg_customer_project')
            ->join('wg_customer_project_costs as pc', function ($join) {
                $join->on('pc.project_id', '=', 'wg_customer_project.id');
                $join->where('pc.status', 'SS002');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('project_type', 'type')), function ($join) {
                $join->on('wg_customer_project.type', '=', 'type.value');
            })
            ->where('customer_id', $customerId)
            ->whereYear('deliveryDate', $year)
            ->whereRaw("( wg_customer_project.type = 'Intm' OR
                         (wg_customer_project.type = 'SYL' AND pc.concept = 'PCOS014') OR
                         (wg_customer_project.type = 'RV'  AND pc.concept = 'C03')
                        )")
            ->orderBy('type.item')
            ->select('type.value', 'type.item')
            ->distinct()
            ->get();
    }

    public function getReportPdfData($criteria)
    {
        $customer = $this->getCustomerPdfReportData($criteria);
        $activities = $this->getAllActivities($criteria);
        $budgets = $this->getAllBudget($criteria);
        $balanceGeneral = $this->getAllBalanceGeneral($criteria);
        $arlServiceCost = $this->getAllArlServiceCost($criteria);
        $behaviorPieChart = $this->getPieChartBehavior($criteria);
        $behaviorAreaChart = $this->getAreaChartBehavior($criteria);
        $notes = $this->getNotes();

        $report['date'] = Carbon::now('America/Bogota')->format('d/m/Y');
        $report['period'] = $criteria->selectedYear;
        $report['customer'] = $customer;
        $report['activities'] = $activities;
        $report['budgets'] = $budgets;
        $report['balanceGeneral'] = $balanceGeneral;
        $report['aditionalServices'] = $arlServiceCost;
        $report['notes'] = $notes;
        $report['themeUrl'] = CmsHelper::getThemeUrl();
        $report['themePath'] = CmsHelper::getThemePath();

        $data = array_merge($report, $behaviorPieChart, $behaviorAreaChart);

        return $data;
    }

    private function getPieChartBehavior($criteria)
    {
        $executions = DB::table('wg_customer_project as pr')
            ->join('wg_customer_project_costs as c', function ($join) {
                $join->on('c.project_id', '=', 'pr.id');
                $join->whereRaw("c.status = 'SS002' ");
            })
            ->where('pr.customer_id', $criteria->customerId)
            ->whereYear('pr.deliveryDate', $criteria->selectedYear)
            ->whereRaw("( pr.type = 'Intm' OR
                         (pr.type = 'SYL' AND c.concept = 'PCOS014') OR
                         (pr.type = 'RV'  AND c.concept = 'C03')
                        )")
            ->select(DB::raw("SUM(c.total_price) AS total"))
            ->first();

        $available = DB::table('wg_customer_arl_contribution as con')
            ->where('con.customer_id', $criteria->customerId)
            ->where('con.year', $criteria->selectedYear)
            ->select(
                DB::raw("SUM( (input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100 ) AS available")
            )
            ->first();

        $available = $available ? (float)$available->available : 20;
        $total = $executions ? (float)$executions->total : 98;

        $availableFormat = "$" . number_format($available);
        $totalFormat = "$" . number_format($total);

        $chartData = [
            ["Reinversión WG \n     ($availableFormat)",  $available],
            ["Ejecutadas \n         ($totalFormat)",  $total],
        ];

        array_unshift($chartData, ['Label', 'Value']);

        return [
            "behaviorPieChart" => [
                "data" => json_encode($chartData)
            ]
        ];
    }

    private function getAreaChartBehavior($criteria)
    {
        $subquery = DB::table("wg_customer_project AS p")
            ->join('wg_customer_project_costs as c', function ($join) {
                $join->on('c.project_id', '=', 'p.id');
                $join->whereRaw("c.status = 'SS002' ");
            })
            ->where('p.customer_id', $criteria->customerId)
            ->whereRaw("YEAR(p.deliveryDate) = $criteria->selectedYear")
            ->whereRaw("( p.type = 'Intm' OR
                         (p.type = 'SYL' AND c.concept = 'PCOS014') OR
                         (p.type = 'RV'  AND c.concept = 'C03')
                        )")
            ->groupBy(DB::raw('MONTH(p.deliveryDate)'))
            ->select(
                DB::raw("'Ejecuciones por año' AS label"),
                DB::raw('MONTH(p.deliveryDate) AS month'),
                DB::raw('SUM(total_price) AS total')
            );

        $data = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.label')
            ->select(
                'd.label',
                DB::raw("MAX(CASE when d.month = 1 then d.total END) AS 'JAN'"),
                DB::raw("MAX(CASE when d.month = 2 then d.total END) AS 'FEB'"),
                DB::raw("MAX(CASE when d.month = 3 then d.total END) AS 'MAR'"),
                DB::raw("MAX(CASE when d.month = 4 then d.total END) AS 'APR'"),
                DB::raw("MAX(CASE when d.month = 5 then d.total END) AS 'MAY'"),
                DB::raw("MAX(CASE when d.month = 6 then d.total END) AS 'JUN'"),
                DB::raw("MAX(CASE when d.month = 7 then d.total END) AS 'JUL'"),
                DB::raw("MAX(CASE when d.month = 8 then d.total END) AS 'AUG'"),
                DB::raw("MAX(CASE when d.month = 9 then d.total END) AS 'SEP'"),
                DB::raw("MAX(CASE when d.month = 10 then d.total END) AS 'OCT'"),
                DB::raw("MAX(CASE when d.month = 11 then d.total END) AS 'NOV'"),
                DB::raw("MAX(CASE when d.month = 12 then d.total END) AS 'DEC'")
            )
            ->first();

        $chartData = [
            ["Ene",  $data ? $this->parseToFloat($data->JAN) : 0],
            ["Feb",  $data ? $this->parseToFloat($data->FEB) : 0],
            ["Mar",  $data ? $this->parseToFloat($data->MAR) : 0],
            ["Abr",  $data ? $this->parseToFloat($data->APR) : 0],
            ["May",  $data ? $this->parseToFloat($data->MAY) : 0],
            ["Jun",  $data ? $this->parseToFloat($data->JUN) : 0],
            ["Jul",  $data ? $this->parseToFloat($data->JUL) : 0],
            ["Ago",  $data ? $this->parseToFloat($data->AUG) : 0],
            ["Sep",  $data ? $this->parseToFloat($data->SEP) : 0],
            ["Oct",  $data ? $this->parseToFloat($data->OCT) : 0],
            ["Nov",  $data ? $this->parseToFloat($data->NOV) : 0],
            ["Dic",  $data ? $this->parseToFloat($data->DEC) : 0],
        ];

        $items = [
            "Ene" =>  $data ? $data->JAN : 0,
            "Feb" =>  $data ? $data->FEB : 0,
            "Mar" =>  $data ? $data->MAR : 0,
            "Abr" =>  $data ? $data->APR : 0,
            "May" =>  $data ? $data->MAY : 0,
            "Jun" =>  $data ? $data->JUN : 0,
            "Jul" =>  $data ? $data->JUL : 0,
            "Ago" =>  $data ? $data->AUG : 0,
            "Sep" =>  $data ? $data->SEP : 0,
            "Oct" =>  $data ? $data->OCT : 0,
            "Nov" =>  $data ? $data->NOV : 0,
            "Dic" =>  $data ? $data->DEC : 0,
        ];

        array_unshift($chartData, ['Label', 'Value']);

        return [
            "behaviorAreaChart" => [
                "data" => json_encode($chartData),
                "items" => $items
            ]
        ];
    }

    private function getCustomerPdfReportData($criteria)
    {
        $customer = DB::table('wg_customers')
            ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail')), function ($join) {
                $join->on('customer_info_detail.entityId', '=', 'wg_customers.id');
            })
            ->leftjoin('wg_customer_agent as vc', function ($join) {
                $join->on('vc.customer_id', '=', 'wg_customers.id');
                $join->where('vc.type', '=', 'vc');
            })
            ->leftjoin('wg_agent as avc', function ($join) {
                $join->on('avc.id', '=', 'vc.agent_id');
            })
            ->leftjoin('wg_customer_agent as gcom', function ($join) {
                $join->on('gcom.customer_id', '=', 'wg_customers.id');
                $join->where('gcom.type', '=', 'gcom');
            })
            ->leftjoin('wg_agent as agcom', function ($join) {
                $join->on('agcom.id', '=', 'gcom.agent_id');
            })
            ->leftjoin('rainlab_user_countries', function ($join) {
                $join->on('rainlab_user_countries.id', '=', 'wg_customers.country_id');
            })
            ->leftjoin('rainlab_user_states', function ($join) {
                $join->on('rainlab_user_states.id', '=', 'wg_customers.state_id');
            })
            ->leftjoin('wg_towns', function ($join) {
                $join->on('wg_towns.id', '=', 'wg_customers.city_id');
            })
            ->select(
                'wg_customers.businessName',
                'wg_customers.documentNumber',
                'rainlab_user_countries.name AS country',
                'rainlab_user_states.name AS state',
                'wg_towns.name AS city',
                'customer_info_detail.address',
                'customer_info_detail.telephone AS phone',
                DB::raw("CONCAT(agcom.name) AS gcom"),
                DB::raw("CONCAT(avc.name) AS gtec")
            )
            ->where('wg_customers.id', $criteria->customerId)
            ->groupBy('wg_customers.id');

        return $customer->first();
    }

    private function getAllActivities($criteria)
    {
        $qSubQuery = DB::table('wg_customer_project')
            ->leftjoin('wg_customer_project_agent', function ($join) {
                $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
            })
            ->leftjoin('wg_customer_project_agent_task AS wg_customer_project_agent_stats', function ($join) {
                $join->on('wg_customer_project_agent_stats.project_agent_id', '=', 'wg_customer_project_agent.id');
            })
            ->leftjoin('wg_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_project_agent.agent_id');
            })
            ->select(
                'wg_customer_project_agent.id',
                'wg_customer_project.id AS project_id',
                'wg_customer_project.deliveryDate AS delivery_date_project',
                DB::raw("ROUND(IFNULL(SUM(IF (wg_customer_project_agent_stats.status = 'activo',duration,0)),0),0) +
                         ROUND(IFNULL(SUM(IF (wg_customer_project_agent_stats.status = 'inactivo',duration,0)),0),0) AS estimated_hours"),
                DB::raw("ROUND(IFNULL(SUM(IF (wg_customer_project_agent_stats.status = 'inactivo',duration,0)),0),0) AS duration"),
                DB::raw("CONCAT(wg_agent.`name`) agent_name")
            )
            ->where('wg_customer_project.customer_id', $criteria->customerId)
            ->groupBy('wg_customer_project.id');

        $queryBase = $this->getQuerySalesByMonth();

        return DB::table(DB::raw("({$queryBase->toSql()}) as o"))
            ->mergeBindings($queryBase)
            ->leftjoin(DB::raw("({$qSubQuery->toSql()}) as c"), function ($join) {
                $join->on('c.project_id', '=', 'o.project_id');
            })
            ->mergeBindings($qSubQuery)
            ->select(
                'o.period',
                'o.type',
                'o.activity',
                'o.concept',
                'o.classification',
                "o.total",
                'o.year',
                'o.month',
                'estimated_hours',
                'agent_name',
                'duration'
            )
            ->where('o.customerId', $criteria->customerId)
            ->where('o.year', $criteria->selectedYear)
            ->get();
    }

    private function getAllBudget($criteria)
    {
        $subquerySales = $this->getQueryExecutedCosts();

        return DB::table('wg_customer_arl_contribution')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month')), function ($join) {
                $join->on('month.value', '=', 'wg_customer_arl_contribution.month');
            })
            ->leftJoin(DB::raw("({$subquerySales->toSql()}) as sales"), function ($join) {
                $join->on('sales.customer_id', 'wg_customer_arl_contribution.customer_id');
                $join->on('sales.year', 'wg_customer_arl_contribution.year');
                $join->on('sales.month', 'wg_customer_arl_contribution.month');
            })
            ->select(
                'wg_customer_arl_contribution.id',
                'wg_customer_arl_contribution.year',
                'month.item AS month',
                'wg_customer_arl_contribution.input',
                'wg_customer_arl_contribution.percent_reinvestment_arl',
                'wg_customer_arl_contribution.percent_reinvestment_wg',
                DB::raw("input * percent_reinvestment_arl / 100 AS reinvestmentARL"),
                DB::raw("(input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100 AS reinvestmentWG"),
                DB::raw("COALESCE (sales.sales, 0) AS sales"),
                DB::raw("COALESCE (
                    (
                        (
                            input * percent_reinvestment_arl / 100
                        ) * percent_reinvestment_wg / 100
                    ) - COALESCE (sales.sales, 0),
                    0
                ) AS balance"),
                'wg_customer_arl_contribution.customer_id',
                'wg_customer_arl_contribution.created_at',
                'wg_customer_arl_contribution.updated_at'
            )
            ->where('wg_customer_arl_contribution.customer_id', $criteria->customerId)
            ->where('wg_customer_arl_contribution.year', $criteria->selectedYear)
            ->get();
    }

    private function getAllBalanceGeneral($criteria)
    {
        $queryMaster = $this->getQueryBalance();
        $queryMasterPrevious = $this->getQueryBalance();

        return DB::table(DB::raw("({$queryMaster->toSql()}) as o"))
            ->mergeBindings($queryMaster)
            ->leftjoin(DB::raw("({$queryMasterPrevious->toSql()}) AS p"), function ($join) {
                $join->on('p.year', '=', DB::raw('o.year - 1'));
                $join->on('p.customerId', '=', 'o.customerId');
            })
            ->mergeBindings($queryMasterPrevious)
            ->select(
                'o.year',
                DB::raw("IF(IFNULL(p.balance, 0) < 0, 0, IFNULL(p.balance, 0)) AS previousBalance"),
                "o.contributions",
                "o.commissions",
                DB::raw("o.reinvesments + IF(IFNULL(p.balance, 0) < 0, 0, IFNULL(p.balance, 0)) AS reinvesments"),
                "o.sales",
                "o.balance",
                'o.customerId'
            )
            ->where('o.customerId', $criteria->customerId)
            ->where('o.year', $criteria->selectedYear)
            ->get();
    }

    private function getAllArlServiceCost($criteria)
    {
        return DB::table('wg_customer_arl_service_cost')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_arl_service')), function ($join) {
                $join->on('customer_arl_service.value', '=', 'wg_customer_arl_service_cost.service');
            })
            ->select(
                "wg_customer_arl_service_cost.id",
                DB::raw("DATE(wg_customer_arl_service_cost.registration_date) AS registration_date"),
                "customer_arl_service.item AS service",
                "wg_customer_arl_service_cost.cost",
                DB::raw("YEAR(wg_customer_arl_service_cost.registration_date) AS year"),
                "wg_customer_arl_service_cost.customer_id"
            )
            ->where('wg_customer_arl_service_cost.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_arl_service_cost.registration_date', $criteria->selectedYear)
            ->get();
    }

    private function getNotes()
    {
        return DB::table('system_parameters')
            ->select(
                "system_parameters.value"
            )
            ->where('system_parameters.group', 'pdf_arl_report_notes')
            ->where('system_parameters.namespace', 'wgroup')
            ->get();
    }

    private function parseToFloat($value)
    {
        if (is_string($value) && $value == null) {
            return 0;
        }

        return (float)$value;
    }
}
