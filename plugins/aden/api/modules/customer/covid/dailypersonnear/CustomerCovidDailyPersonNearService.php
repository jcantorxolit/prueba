<?php

namespace AdeN\Api\Modules\Customer\Covid\DailyPersonNear;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;

class CustomerCovidDailyPersonNearService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getExportData($criteria)
    {
        $query = DB::table('wg_customer_covid_person_near');
        $query->join("wg_customer_covid", function($join){
                $join->on("wg_customer_covid_person_near.customer_covid_id","=","wg_customer_covid.id");
            })
            ->join("wg_customer_covid_head", function ($join) {
                $join->on('wg_customer_covid_head.id', '=', 'wg_customer_covid.id');
            })
            ->join("wg_customer_manacle_employee", function ($join) {
                $join->on('wg_customer_covid_person_near.manacle_employee_id', '=', 'wg_customer_manacle_employee.id');
            })
            ->join("wg_customer_manacle", function ($join) {
                $join->on('wg_customer_manacle_employee.manacle_id', '=', 'wg_customer_manacle.id');
            })
            ->leftjoin("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_manacle_employee.customer_employee_id');
            })
            ->leftjoin("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_employee.workPlace');
            })
            ->select(
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%d/%m/%Y  %T') AS registration_date"),
                "wg_customer_manacle.number AS manacleNumber",
                "wg_customer_covid_person_near.distance",
                "wg_employee.firstName AS firstName",
                "wg_employee.lastName AS lastName",
                "wg_customer_config_workplace.name as customerWorkplace",
                'wg_customer_covid.id AS customerCovidId'
            );

        $query = $this->prepareQuery($query->toSql())
            ->mergeBindings($query);

        $this->applyWhere($query, $criteria);

        return ExportHelper::headings($query->get(), $this->getHeader());
    }

    public function getHeader()
    {
        return  [
            "FECHA REGISTRO" => "registration_date",
            "ID MANILLA" => "manacleNumber",
            "DISTANCIA" => "distance",
            "NOMBRE(S)" => "firstName",
            "APELLIDOS" => "lastName",
            "CENTRO DE TRABAJO" => "customerWorkplace",
        ];
    }
    
}