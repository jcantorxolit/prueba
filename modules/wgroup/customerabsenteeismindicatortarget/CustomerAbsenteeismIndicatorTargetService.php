<?php

namespace Wgroup\CustomerAbsenteeismIndicatorTarget;

use DB;
use Exception;
use Log;
use Str;

class CustomerAbsenteeismIndicatorTargetService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerAbsenteeismDisabilityRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $year = 0, $workCenter = "", $classification = "") {

        $model = new CustomerAbsenteeismIndicatorTarget();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerAbsenteeismDisabilityRepository = new CustomerAbsenteeismIndicatorTargetRepository($model);

        if ($perPage > 0) {
            $this->customerAbsenteeismDisabilityRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_absenteeism_indicator_target.id',
            'wg_customer_absenteeism_indicator_target.period',
            'wg_customer_absenteeism_indicator_target.targetIF',
            'wg_customer_absenteeism_indicator_target.targetIS',
            'wg_customer_absenteeism_indicator_target.targetILI',
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
                    $this->customerAbsenteeismDisabilityRepository->sortBy($colName, $dir);
                } else {
                    $this->customerAbsenteeismDisabilityRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerAbsenteeismDisabilityRepository->sortBy('wg_customer_absenteeism_indicator_target.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_indicator_target.customer_id', $customerId);

        if ($year != 0) {
            //$filters[] = array('wg_customer_absenteeism_indicator_target.customer_id', $year);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_absenteeism_indicator_target.period', $search);
            $filters[] = array('wg_customer_absenteeism_indicator_target.targetIF', $search);
            $filters[] = array('wg_customer_absenteeism_indicator_target.targetIS', $search);
            $filters[] = array('wg_customer_absenteeism_indicator_target.targetILI', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_absenteeism_indicator_target.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_absenteeism_indicator_target.status', '0');
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_absenteeism_indicator_target.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, false, "");
    }

    public function getSummaryDisability($perPage = 10, $currentPage = 0,$customerId, $cause = "")
    {
        $startFrom = ($currentPage-1) * $perPage;

        $query = "select count(*) quantity, p.item cause, DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType
from wg_customer_absenteeism_indicator_target cd
inner join wg_customers c on c.id = cd.customer_id
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes') p on cd.cause = p.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_type') dt on cd.type COLLATE utf8_general_ci = dt.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_contract_type') ct on cd.contractType COLLATE utf8_general_ci = ct.value";

        $whereArray = array();

        $where = " where cd.customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;

        $group = " group by p.item, DATE_FORMAT(`start`,'%Y%m'), ct.item";

        $limit = " LIMIT $startFrom , $perPage";

        if ($cause != "") {
            $where .= " AND p.value = :cause";
            $whereArray["cause"] = $cause;
        }

        $sql = $query.$where.$group.$limit;

        //Log::info($cause);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getSummaryDisabilityReport($customerId, $year, $cause = "")
    {
        $query = "Select
                    sum(case when MONTH(wgc.start) = 1 then wgc.amountPaid else 0 end) 'Enero',
                    sum(case when MONTH(wgc.start) = 2 then wgc.amountPaid else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.start) = 3 then wgc.amountPaid else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.start) = 4 then wgc.amountPaid else 0 end) 'Abril',
                    sum(case when MONTH(wgc.start) = 5 then wgc.amountPaid else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.start) = 6 then wgc.amountPaid else 0 end) 'Junio',
                    sum(case when MONTH(wgc.start) = 7 then wgc.amountPaid else 0 end) 'Julio',
                    sum(case when MONTH(wgc.start) = 8 then wgc.amountPaid else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.start) = 9 then wgc.amountPaid else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.start) = 10 then wgc.amountPaid else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.start) = 11 then wgc.amountPaid else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.start) = 12 then wgc.amountPaid else 0 end) 'Diciembre'
            from wg_customer_absenteeism_indicator_target wgc ";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.start) = :currentYear and customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($cause != "") {
            $where .= " AND cause = :cause";
            $whereArray["cause"] = $cause;
        }

        $sql = $query.$where;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getSummaryDisabilityReportYears($customerId)
    {
        $sql = "select distinct YEAR(wgc.start) id, YEAR(wgc.start) item, YEAR(wgc.start) value
                from wg_customer_absenteeism_indicator_target wgc
                where customer_id = :customer_id
                order by 1 desc";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getSummaryWorkCenter($customerId)
    {
        $sql = "select distinct ce.workPlace id, ce.workPlace item, ce.workPlace value
                from wg_customer_absenteeism_disability wgc
                inner join wg_customer_employee ce on ce.id = wgc.customer_employee_id
                where ce.customer_id = :customer_id
                order by 1 desc";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getEventNumberChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    classification,
                    workCenter,
                    sum(case when MONTH(wgc.periodDate) = 1 then wgc.eventNumber else 0 end) 'Enero',
                    sum(case when MONTH(wgc.periodDate) = 2 then wgc.eventNumber else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.periodDate) = 3 then wgc.eventNumber else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.periodDate) = 4 then wgc.eventNumber else 0 end) 'Abril',
                    sum(case when MONTH(wgc.periodDate) = 5 then wgc.eventNumber else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.periodDate) = 6 then wgc.eventNumber else 0 end) 'Junio',
                    sum(case when MONTH(wgc.periodDate) = 7 then wgc.eventNumber else 0 end) 'Julio',
                    sum(case when MONTH(wgc.periodDate) = 8 then wgc.eventNumber else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.periodDate) = 9 then wgc.eventNumber else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.periodDate) = 10 then wgc.eventNumber else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.periodDate) = 11 then wgc.eventNumber else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.periodDate) = 12 then wgc.eventNumber else 0 end) 'Diciembre',
                    sum(wgc.eventNumber) 'Total'
                from wg_customer_absenteeism_indicator_target wgc";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.periodDate) = :currentYear and customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($classification != "") {
            $where .= " AND classification = :classification";
            $whereArray["classification"] = $classification;
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = :workCenter";
            $whereArray["workCenter"] = $workCenter;
        }

        $group = " group by YEAR(wgc.periodDate)";

        $sql = $query.$where.$group;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getDisabilityDaysChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    classification,
                    workCenter,
                    sum(case when MONTH(wgc.periodDate) = 1 then wgc.disabilityDays else 0 end) 'Enero',
                    sum(case when MONTH(wgc.periodDate) = 2 then wgc.disabilityDays else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.periodDate) = 3 then wgc.disabilityDays else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.periodDate) = 4 then wgc.disabilityDays else 0 end) 'Abril',
                    sum(case when MONTH(wgc.periodDate) = 5 then wgc.disabilityDays else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.periodDate) = 6 then wgc.disabilityDays else 0 end) 'Junio',
                    sum(case when MONTH(wgc.periodDate) = 7 then wgc.disabilityDays else 0 end) 'Julio',
                    sum(case when MONTH(wgc.periodDate) = 8 then wgc.disabilityDays else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.periodDate) = 9 then wgc.disabilityDays else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.periodDate) = 10 then wgc.disabilityDays else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.periodDate) = 11 then wgc.disabilityDays else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.periodDate) = 12 then wgc.disabilityDays else 0 end) 'Diciembre',
                    sum(wgc.disabilityDays) 'Total'
                from wg_customer_absenteeism_indicator_target wgc";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.periodDate) = :currentYear and customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($classification != "") {
            $where .= " AND classification = :classification";
            $whereArray["classification"] = $classification;
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = :workCenter";
            $whereArray["workCenter"] = $workCenter;
        }

        $group = " group by YEAR(wgc.periodDate)";

        $sql = $query.$where.$group;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getIFChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    classification,
                    workCenter,
                    sum(case when MONTH(wgc.periodDate) = 1 then wgc.frequencyIndex else 0 end) 'Enero',
                    sum(case when MONTH(wgc.periodDate) = 2 then wgc.frequencyIndex else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.periodDate) = 3 then wgc.frequencyIndex else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.periodDate) = 4 then wgc.frequencyIndex else 0 end) 'Abril',
                    sum(case when MONTH(wgc.periodDate) = 5 then wgc.frequencyIndex else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.periodDate) = 6 then wgc.frequencyIndex else 0 end) 'Junio',
                    sum(case when MONTH(wgc.periodDate) = 7 then wgc.frequencyIndex else 0 end) 'Julio',
                    sum(case when MONTH(wgc.periodDate) = 8 then wgc.frequencyIndex else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.periodDate) = 9 then wgc.frequencyIndex else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.periodDate) = 10 then wgc.frequencyIndex else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.periodDate) = 11 then wgc.frequencyIndex else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.periodDate) = 12 then wgc.frequencyIndex else 0 end) 'Diciembre',
                    sum(wgc.frequencyIndex) 'Total'
                from wg_customer_absenteeism_indicator_target wgc";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.periodDate) = :currentYear and customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($classification != "") {
            $where .= " AND classification = :classification";
            $whereArray["classification"] = $classification;
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = :workCenter";
            $whereArray["workCenter"] = $workCenter;
        }

        $group = " group by YEAR(wgc.periodDate)";

        $sql = $query.$where.$group;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getISChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    classification,
                    workCenter,
                    sum(case when MONTH(wgc.periodDate) = 1 then wgc.severityIndex else 0 end) 'Enero',
                    sum(case when MONTH(wgc.periodDate) = 2 then wgc.severityIndex else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.periodDate) = 3 then wgc.severityIndex else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.periodDate) = 4 then wgc.severityIndex else 0 end) 'Abril',
                    sum(case when MONTH(wgc.periodDate) = 5 then wgc.severityIndex else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.periodDate) = 6 then wgc.severityIndex else 0 end) 'Junio',
                    sum(case when MONTH(wgc.periodDate) = 7 then wgc.severityIndex else 0 end) 'Julio',
                    sum(case when MONTH(wgc.periodDate) = 8 then wgc.severityIndex else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.periodDate) = 9 then wgc.severityIndex else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.periodDate) = 10 then wgc.severityIndex else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.periodDate) = 11 then wgc.severityIndex else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.periodDate) = 12 then wgc.severityIndex else 0 end) 'Diciembre',
                    sum(wgc.severityIndex) 'Total'
                from wg_customer_absenteeism_indicator_target wgc";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.periodDate) = :currentYear and customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($classification != "") {
            $where .= " AND classification = :classification";
            $whereArray["classification"] = $classification;
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = :workCenter";
            $whereArray["workCenter"] = $workCenter;
        }

        $group = " group by YEAR(wgc.periodDate)";

        $sql = $query.$where.$group;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getILIChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    classification,
                    workCenter,
                    sum(case when MONTH(wgc.periodDate) = 1 then wgc.disablingInjuriesIndex else 0 end) 'Enero',
                    sum(case when MONTH(wgc.periodDate) = 2 then wgc.disablingInjuriesIndex else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.periodDate) = 3 then wgc.disablingInjuriesIndex else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.periodDate) = 4 then wgc.disablingInjuriesIndex else 0 end) 'Abril',
                    sum(case when MONTH(wgc.periodDate) = 5 then wgc.disablingInjuriesIndex else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.periodDate) = 6 then wgc.disablingInjuriesIndex else 0 end) 'Junio',
                    sum(case when MONTH(wgc.periodDate) = 7 then wgc.disablingInjuriesIndex else 0 end) 'Julio',
                    sum(case when MONTH(wgc.periodDate) = 8 then wgc.disablingInjuriesIndex else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.periodDate) = 9 then wgc.disablingInjuriesIndex else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.periodDate) = 10 then wgc.disablingInjuriesIndex else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.periodDate) = 11 then wgc.disablingInjuriesIndex else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.periodDate) = 12 then wgc.disablingInjuriesIndex else 0 end) 'Diciembre',
                    sum(wgc.disablingInjuriesIndex) 'Total'
                from wg_customer_absenteeism_indicator_target wgc";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.periodDate) = :currentYear and customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($classification != "") {
            $where .= " AND classification = :classification";
            $whereArray["classification"] = $classification;
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = :workCenter";
            $whereArray["workCenter"] = $workCenter;
        }

        $group = " group by YEAR(wgc.periodDate)";

        $sql = $query.$where.$group;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getEventNumberReport($customerId, $year, $workCenter = "", $classification = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(eventNumber, 0) eventNumber
                                ,IFNULL(targetEvent, 0) targetEvent
                    from system_parameters p
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.eventNumber) eventNumber,
                                                        SUM(indicator.targetEvent) targetEvent
                                        from wg_customer_absenteeism_indicator_target indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getDisabilityDaysReport($customerId, $year, $workCenter = "", $classification = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(disabilityDays, 0) disabilityDays
                                ,IFNULL(targetDisabilityDays, 0) targetDisabilityDays
                    from system_parameters p
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.disabilityDays) disabilityDays,
                                                        SUM(indicator.targetDisabilityDays) targetDisabilityDays
                                        from wg_customer_absenteeism_indicator_target indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getIFReport($customerId, $year, $workCenter = "", $classification = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(frequencyIndex, 0) frequencyIndex
                                ,IFNULL(targetFrequencyIndex, 0) targetFrequencyIndex
                    from system_parameters p
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.frequencyIndex) frequencyIndex,
                                                        SUM(indicator.targetFrequencyIndex) targetFrequencyIndex
                                        from wg_customer_absenteeism_indicator_target indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getISReport($customerId, $year, $workCenter = "", $classification = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(severityIndex, 0) severityIndex
                                ,IFNULL(targetSeverityIndex, 0) targetSeverityIndex
                    from system_parameters p
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.severityIndex) severityIndex,
                                                        SUM(indicator.targetSeverityIndex) targetSeverityIndex
                                        from wg_customer_absenteeism_indicator_target indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getILIReport($customerId, $year, $workCenter = "", $classification = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(disablingInjuriesIndex, 0) disablingInjuriesIndex
                                ,IFNULL(targetWorkAccident, 0) targetWorkAccident
                    from system_parameters p
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.disablingInjuriesIndex) disablingInjuriesIndex,
                                                        SUM(indicator.targetWorkAccident) targetWorkAccident
                                        from wg_customer_absenteeism_indicator_target indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerAbsenteeismIndicatorTarget();
        $this->customerAbsenteeismDisabilityRepository = new CustomerAbsenteeismIndicatorTargetRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_indicator_target.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_absenteeism_indicator_target.period', $search);
            $filters[] = array('wg_customer_absenteeism_indicator_target.targetIF', $search);
            $filters[] = array('wg_customer_absenteeism_indicator_target.targetIS', $search);
            $filters[] = array('wg_customer_absenteeism_indicator_target.targetILI', $search);
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_absenteeism_indicator_target.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, true, "");
    }
}
