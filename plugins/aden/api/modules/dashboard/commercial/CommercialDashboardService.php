<?php

namespace AdeN\Api\Modules\Dashboard\Commercial;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Modules\Customer\Licenses\LicenseModel;
use Illuminate\Support\Facades\DB;

use Wgroup\SystemParameter\SystemParameter;

class CommercialDashboardService extends BaseService
{
    public function consolidate() {
        DB::table("wg_customer_licenses_consolidate")->delete();

        $subqueryLastLicense = DB::table('wg_customer_licenses')
            ->select('id',
                DB::raw("ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY start_date DESC ) AS row")
            );



        $query = DB::table('wg_customer_licenses')
            ->groupBy(DB::raw('year(start_date)'), 'license', 'state', 'agent_id')
            ->orderBy(DB::raw('year(start_date)'))
            ->select(
                DB::raw('year(start_date) as year'), 'license', 'state', 'agent_id',
                DB::raw('count(*) AS total')
            );

        $sql = "INSERT INTO wg_customer_licenses_consolidate (year, license, state, agent_id, total) "
             . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }


    public function getChartLineLicensesByYearsHistorical($user) {
        $subquery = DB::table('wg_customer_licenses_consolidate as c')
            ->leftJoin('wg_agent as ag', 'ag.id', '=', 'c.agent_id')
            ->when($user->wg_type != 'system', function($query) use ($user) {
                $query->where('ag.user_id', $user->id);
            })
            ->groupBy('year')
            ->select(
                DB::raw("'Total' AS label"),
                DB::raw("year AS dynamicColumn"),
                DB::raw("sum(total) AS total")
            );

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartLineLicensesByTypeAndYearsHistorical($user) {
        $subquery = DB::table('wg_customer_licenses_consolidate as c')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'sp')), function ($join) {
                $join->on('c.license', '=', 'sp.value');
            })
            ->leftJoin('wg_agent as ag', 'ag.id', '=', 'c.agent_id')
            ->when($user->wg_type != 'system', function($query) use ($user) {
                $query->where('ag.user_id', $user->id);
            })
            ->groupBy('year', 'license')
            ->select(
                DB::raw("sp.item AS label"),
                DB::raw("year AS dynamicColumn"),
                DB::raw("sum(total) AS total")
            );

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartPieActiveLicensesByType($user) {
        $data = $this->getQueryCurrentLicenses($user)
            ->join(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'sp')), function ($join) {
                $join->on('l1.license', '=', 'sp.value');
            })
            ->where('l1.state', LicenseModel::STATE_ACTIVE)
            ->groupBy('l1.license')
            ->get();

        $this->addPercentToLabel($data);
        return $this->chart->getChartPie($data);
    }


    public function getChartPieActiveLicensesByState($user) {
        $data = $this->getQueryCurrentLicenses($user)
            ->join(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_states', 'sp')), function ($join) {
                $join->on('l1.state', '=', 'sp.value');
            })
            ->groupBy('l1.state')
            ->get();

        $this->addPercentToLabel($data);
        return $this->chart->getChartPie($data);
    }


    private function getQueryCurrentLicenses($user)
    {
        $subqueryLastLicense = DB::table('wg_customer_licenses')
            ->select('id',
                DB::raw("ROW_NUMBER() OVER (PARTITION BY customer_id ORDER BY id DESC ) AS row")
            );

        return DB::table('wg_customer_licenses as l1')
            ->join(DB::raw("({$subqueryLastLicense->toSql()}) as t"), function($join) {
                $join->on('t.id', '=', 'l1.id');
                $join->where('t.row', 1);
            })
            ->leftJoin('wg_agent as ag', 'ag.id', '=', 'l1.agent_id')
            ->when($user->wg_type != 'system', function($query) use ($user) {
                $query->where('ag.user_id', $user->id);
            })
            ->select(
                DB::raw("sp.item as label"),
                DB::raw('count(*) AS value')
            );
    }

}
