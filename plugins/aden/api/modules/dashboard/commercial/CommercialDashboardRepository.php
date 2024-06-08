<?php

namespace AdeN\Api\Modules\Dashboard\Commercial;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\Licenses\LicenseModel;
use Illuminate\Support\Facades\DB;
use Wgroup\SystemParameter\SystemParameter;

class CommercialDashboardRepository extends BaseRepository
{
    protected $service;

    public function __construct() {
        parent::__construct(new CommercialDashboardModel());
        $this->service = new CommercialDashboardService();
    }

    public function all($criteria) {
        $this->setColumns([
            "id" => "lic.id as id",
            "customer" => "c.businessName as customer",
            "license" => "license.item as license",
            "startDate" => DB::raw("DATE_FORMAT(lic.start_date,'%d/%m/%Y') as startDate"),
            "finishDate" => DB::raw("DATE_FORMAT(lic.end_date,'%d/%m/%Y') as finishDate"),
            "agent" => "a.name as agent",
            "value" => "lic.value",
            "state" => "state.item as state"
        ]);

        $this->parseCriteria($criteria);

        $user = $this->getAuthUser();

        $query = DB::table("wg_customer_licenses as lic")
            ->join('wg_customers as c', 'c.id', '=', 'lic.customer_id')
            ->join('wg_agent as a', 'a.id', '=', 'lic.agent_id')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'license')), function ($join) {
                $join->on('lic.license', '=', 'license.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_states', 'state')), function ($join) {
                $join->on('lic.state', '=', 'state.value');
            })
            ->when($user->wg_type != 'system', function($query) use ($user) {
                $query->where('a.user_id', $user->id);
            })
            ->where('lic.state', '<>', LicenseModel::STATE_FINISH)
            ->whereRaw("end_date BETWEEN date_format(date_add(now(), INTERVAL -5 HOUR), '%Y-%m-%d') AND date_format(DATE_ADD(date_add(now(), INTERVAL -5 HOUR), INTERVAL 3 MONTH), '%Y-%m-%d')");

        $this->query($query);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function consolidate() {
        $this->service->consolidate();
    }


    public function getChartLineLicensesByYearsHistorical() {
        return $this->service->getChartLineLicensesByYearsHistorical($this->getAuthUser());
    }

    public function getChartLineLicensesByTypeAndYearsHistorical() {
        return $this->service->getChartLineLicensesByTypeAndYearsHistorical($this->getAuthUser());
    }

    public function getChartPieActiveLicensesByType() {
        return $this->service->getChartPieActiveLicensesByType($this->getAuthUser());
    }

    public function getChartPieActiveLicensesByState() {
        return $this->service->getChartPieActiveLicensesByState($this->getAuthUser());
    }

}