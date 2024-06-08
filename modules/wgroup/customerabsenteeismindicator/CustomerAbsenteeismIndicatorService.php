<?php

namespace Wgroup\CustomerAbsenteeismIndicator;

use DB;
use Exception;
use Log;
use Str;
use Carbon\Carbon;

class CustomerAbsenteeismIndicatorService {

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

        $model = new CustomerAbsenteeismIndicator();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerAbsenteeismDisabilityRepository = new CustomerAbsenteeismIndicatorRepository($model);

        if ($perPage > 0) {
            $this->customerAbsenteeismDisabilityRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_absenteeism_indicator.id',
            'wg_customer_absenteeism_indicator.classification',
            'wg_customer_absenteeism_indicator.period',
            'wg_customer_absenteeism_indicator.workCenter',
            'wg_customer_absenteeism_indicator.manHoursWorked',
            'wg_customer_absenteeism_indicator.diseaseRate',
            'wg_customer_absenteeism_indicator.frequencyIndex',
            'wg_customer_absenteeism_indicator.severityIndex',
            'wg_customer_absenteeism_indicator.disablingInjuriesIndex'
        ];

        $i = 0;

        if (empty($sorting)) {
            $this->customerAbsenteeismDisabilityRepository->sortBy('wg_customer_absenteeism_indicator.period', 'desc');
        }

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



        $filters = array();

        $filters[] = array('wg_customer_absenteeism_indicator.customer_id', $customerId);

        if ($year != 0) {
            //$filters[] = array('wg_customer_absenteeism_indicator.customer_id', $year);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_absenteeism_indicator.classification', $search);
            $filters[] = array('wg_customer_absenteeism_indicator.period', $search);
            $filters[] = array('wg_customer_absenteeism_indicator.workCenter', $search);
            $filters[] = array('dtype.item', $search);
            $filters[] = array('ctype.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_absenteeism_indicator.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_absenteeism_indicator.status', '0');
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_absenteeism_indicator.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, false, "");
    }

    public function getSummaryDisability($perPage = 10, $currentPage = 0,$customerId, $cause = "")
    {
        $startFrom = ($currentPage-1) * $perPage;

        $query = "select count(*) quantity, p.item cause, DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType
from wg_customer_absenteeism_indicator cd
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
            from wg_customer_absenteeism_indicator wgc ";

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
                from wg_customer_absenteeism_indicator wgc
                where customer_id = :customer_id
                order by 1 desc";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getSummaryWorkCenter($customerId)
    {
        $sql = "select distinct w.id, w.name item, w.id value
                from wg_customer_absenteeism_disability wgc
                inner join wg_customer_employee ce on ce.id = wgc.customer_employee_id
								inner join wg_customer_config_workplace w on ce.workPlace = w.id
                where ce.customer_id = :customer_id
                order by 1 desc";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getEventNumberChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "
Select
    'Indicatores' label
    , '#e0d653' color
    , customer_id
    , YEAR(wgc.periodDate) year
    , sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
    , sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
    , sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
    , sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
    , sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
    , sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
    , sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
    , sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
    , sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
    , sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
    , sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
    , sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
    , sum(wgc.eventNumber) 'Total'
from wg_customer_absenteeism_indicator wgc";

        $sqlTarget = "select 'Meta' label, '#5cb85c' color, customer_id, YEAR(periodDate)
		, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.targetEvent,0),2) end) ENE
		, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.targetEvent,0),2) end) FEB
		, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.targetEvent,0),2) end) MAR
		, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.targetEvent,0),2) end) ABR
		, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.targetEvent,0),2) end) MAY
		, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.targetEvent,0),2) end) JUN
		, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.targetEvent,0),2) end) JUL
		, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.targetEvent,0),2) end) AGO
		, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.targetEvent,0),2) end) SEP
		, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.targetEvent,0),2) end) OCT
		, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.targetEvent,0),2) end) NOV
		, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.targetEvent,0),2) end) DIC
		 , sum(wgc.targetEvent) 'Total'
FROM
wg_customer_absenteeism_indicator_target wgc
where customer_id = $customerId AND YEAR(periodDate) = $year
group by customer_id, YEAR(periodDate)";

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

        $group = " group by customer_id, YEAR(wgc.periodDate)";

        $sql = $query.$where.$group. " union all " .$sqlTarget;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getDisabilityDaysChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    'Indicatores' label
                    , '#e0d653' color
                    , customer_id
                    , YEAR(wgc.periodDate) year
                    , sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) ENE
                    , sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) FEB
                    , sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) MAR
                    , sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) ABR
                    , sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) MAY
                    , sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) JUN
                    , sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) JUL
                    , sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) AGO
                    , sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) SEP
                    , sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) OCT
                    , sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) NOV
                    , sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.disabilityDays,0),2) end) DIC
                    , sum(wgc.disabilityDays) 'Total'
                from wg_customer_absenteeism_indicator wgc";

        $sqlTarget = "select 'Meta' label, '#5cb85c' color, customer_id, YEAR(periodDate)
		, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.targetDay,0),2) end) ENE
		, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.targetDay,0),2) end) FEB
		, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.targetDay,0),2) end) MAR
		, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.targetDay,0),2) end) ABR
		, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.targetDay,0),2) end) MAY
		, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.targetDay,0),2) end) JUN
		, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.targetDay,0),2) end) JUL
		, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.targetDay,0),2) end) AGO
		, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.targetDay,0),2) end) SEP
		, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.targetDay,0),2) end) OCT
		, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.targetDay,0),2) end) NOV
		, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.targetDay,0),2) end) DIC
		 , sum(wgc.targetEvent) 'Total'
FROM
wg_customer_absenteeism_indicator_target wgc
where customer_id = $customerId AND YEAR(periodDate) = $year
group by customer_id, YEAR(periodDate)";

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

        $sql = $query.$where.$group. " union all " .$sqlTarget;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getIFChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    'Indicatores' label
                    , '#e0d653' color
                    , customer_id
                    , YEAR(wgc.periodDate) year
                    , sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) ENE
                    , sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) FEB
                    , sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) MAR
                    , sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) ABR
                    , sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) MAY
                    , sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) JUN
                    , sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) JUL
                    , sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) AGO
                    , sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) SEP
                    , sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) OCT
                    , sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) NOV
                    , sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.frequencyIndex,0),2) end) DIC
                    , sum(wgc.frequencyIndex) 'Total'
                from wg_customer_absenteeism_indicator wgc";

        $sqlTarget = "select 'Meta' label, '#5cb85c' color, customer_id, YEAR(periodDate)
		, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.targetIF,0),2) end) ENE
		, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.targetIF,0),2) end) FEB
		, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.targetIF,0),2) end) MAR
		, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.targetIF,0),2) end) ABR
		, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.targetIF,0),2) end) MAY
		, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.targetIF,0),2) end) JUN
		, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.targetIF,0),2) end) JUL
		, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.targetIF,0),2) end) AGO
		, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.targetIF,0),2) end) SEP
		, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.targetIF,0),2) end) OCT
		, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.targetIF,0),2) end) NOV
		, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.targetIF,0),2) end) DIC
		 , sum(wgc.targetEvent) 'Total'
FROM
wg_customer_absenteeism_indicator_target wgc
where customer_id = $customerId AND YEAR(periodDate) = $year
group by customer_id, YEAR(periodDate)";

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

        $sql = $query.$where.$group. " union all " .$sqlTarget;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getISChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    'Indicatores' label
                    , '#e0d653' color
                    , customer_id
                    , YEAR(wgc.periodDate) year
                    , sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.severityIndex,0),2) end) ENE
                    , sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.severityIndex,0),2) end) FEB
                    , sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.severityIndex,0),2) end) MAR
                    , sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.severityIndex,0),2) end) ABR
                    , sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.severityIndex,0),2) end) MAY
                    , sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.severityIndex,0),2) end) JUN
                    , sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.severityIndex,0),2) end) JUL
                    , sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.severityIndex,0),2) end) AGO
                    , sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.severityIndex,0),2) end) SEP
                    , sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.severityIndex,0),2) end) OCT
                    , sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.severityIndex,0),2) end) NOV
                    , sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.severityIndex,0),2) end) DIC
                    , sum(wgc.severityIndex) 'Total'
                from wg_customer_absenteeism_indicator wgc";

        $sqlTarget = "select 'Meta' label, '#5cb85c' color, customer_id, YEAR(periodDate)
		, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.targetIS,0),2) end) ENE
		, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.targetIS,0),2) end) FEB
		, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.targetIS,0),2) end) MAR
		, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.targetIS,0),2) end) ABR
		, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.targetIS,0),2) end) MAY
		, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.targetIS,0),2) end) JUN
		, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.targetIS,0),2) end) JUL
		, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.targetIS,0),2) end) AGO
		, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.targetIS,0),2) end) SEP
		, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.targetIS,0),2) end) OCT
		, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.targetIS,0),2) end) NOV
		, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.targetIS,0),2) end) DIC
		 , sum(wgc.targetEvent) 'Total'
FROM
wg_customer_absenteeism_indicator_target wgc
where customer_id = $customerId AND YEAR(periodDate) = $year
group by customer_id, YEAR(periodDate)";

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

        $sql = $query.$where.$group. " union all " .$sqlTarget;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getILIChart($customerId, $year, $workCenter = "", $classification = "")
    {
        $query = "Select
                    'Indicatores' label
                    , '#e0d653' color
                    , customer_id
                    , YEAR(wgc.periodDate) year
                    , sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) ENE
                    , sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) FEB
                    , sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) MAR
                    , sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) ABR
                    , sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) MAY
                    , sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) JUN
                    , sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) JUL
                    , sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) AGO
                    , sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) SEP
                    , sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) OCT
                    , sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) NOV
                    , sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.disablingInjuriesIndex,0),2) end) DIC
                    , sum(wgc.disablingInjuriesIndex) 'Total'
                from wg_customer_absenteeism_indicator wgc";

        $sqlTarget = "select 'Meta' label, '#5cb85c' color, customer_id, YEAR(periodDate)
		, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.targetILI,0),2) end) ENE
		, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.targetILI,0),2) end) FEB
		, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.targetILI,0),2) end) MAR
		, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.targetILI,0),2) end) ABR
		, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.targetILI,0),2) end) MAY
		, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.targetILI,0),2) end) JUN
		, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.targetILI,0),2) end) JUL
		, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.targetILI,0),2) end) AGO
		, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.targetILI,0),2) end) SEP
		, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.targetILI,0),2) end) OCT
		, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.targetILI,0),2) end) NOV
		, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.targetILI,0),2) end) DIC
		 , sum(wgc.targetEvent) 'Total'
FROM
wg_customer_absenteeism_indicator_target wgc
where customer_id = $customerId AND YEAR(periodDate) = $year
group by customer_id, YEAR(periodDate)";

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

        $sql = $query.$where.$group. " union all " .$sqlTarget;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getEventNumberReport($customerId, $year, $workCenter = "", $classification = "", $resolution = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year AND resolution = '$resolution' ";

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
                    left join (SELECT * FROM wg_customer_absenteeism_indicator_target WHERE YEAR(periodDate) = $year AND customer_id = $customerId) t on p.`value` = MONTH(t.periodDate)
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.eventNumber) eventNumber
                                        from wg_customer_absenteeism_indicator indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getDisabilityDaysReport($customerId, $year, $workCenter = "", $classification = "", $resolution = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year AND resolution = '$resolution' ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(disabilityDays, 0) disabilityDays
                                ,IFNULL(targetDay, 0) targetDisabilityDays
                    from system_parameters p
                    left join (SELECT * FROM wg_customer_absenteeism_indicator_target WHERE YEAR(periodDate) = $year AND customer_id = $customerId) t on p.`value` = MONTH(t.periodDate)
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.disabilityDays) disabilityDays
                                        from wg_customer_absenteeism_indicator indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getIFReport($customerId, $year, $workCenter = "", $classification = "", $resolution = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year AND resolution = '$resolution' ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(frequencyIndex, 0) frequencyIndex
                                ,IFNULL(targetIF, 0) targetFrequencyIndex
                    from system_parameters p
                    left join (SELECT * FROM wg_customer_absenteeism_indicator_target WHERE YEAR(periodDate) = $year AND customer_id = $customerId) t on p.`value` = MONTH(t.periodDate)
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.frequencyIndex) frequencyIndex
                                        from wg_customer_absenteeism_indicator indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getISReport($customerId, $year, $workCenter = "", $classification = "", $resolution = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year AND resolution = '$resolution' ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(severityIndex, 0) severityIndex
                                ,IFNULL(targetIS, 0) targetSeverityIndex
                    from system_parameters p
                    left join (SELECT * FROM wg_customer_absenteeism_indicator_target WHERE YEAR(periodDate) = $year AND customer_id = $customerId) t on p.`value` = MONTH(t.periodDate)
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.severityIndex) severityIndex
                                        from wg_customer_absenteeism_indicator indicator
                                        $where
                                        group by MONTH(indicator.periodDate)) wca on p.`value` = wca.monthNumber
                    where namespace = 'wgroup' and `group` = 'month'
                    ORDER BY nIndex";



        $sql = $query;

        //Log::info($sql);

        $results = DB::select($sql);

        return $results;
    }

    public function getILIReport($customerId, $year, $workCenter = "", $classification = "", $resolution = "")
    {
        $where = " where indicator.customer_id = $customerId and YEAR(indicator.periodDate) = $year AND resolution = '$resolution' ";

        if ($classification != "") {
            $where .= " AND classification = '$classification'";
        }

        if ($workCenter != "") {
            $where .= " AND workCenter = '$workCenter'";
        }

        $query = "select item, CONVERT(p.`value`,UNSIGNED INTEGER) nIndex
                                ,workCenter
                                ,IFNULL(disablingInjuriesIndex, 0) disablingInjuriesIndex
                                ,IFNULL(targetILI, 0) targetWorkAccident
                    from system_parameters p
                    left join (SELECT * FROM wg_customer_absenteeism_indicator_target WHERE YEAR(periodDate) = $year AND customer_id = $customerId) t on p.`value` = MONTH(t.periodDate)
                    left join (select 	classification,
                                                        workCenter,
                                                        MONTH(indicator.periodDate) monthNumber,
                                                        SUM(indicator.disablingInjuriesIndex) disablingInjuriesIndex
                                        from wg_customer_absenteeism_indicator indicator
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

        $model = new CustomerAbsenteeismIndicator();
        $this->customerAbsenteeismDisabilityRepository = new CustomerAbsenteeismIndicatorRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_indicator.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_absenteeism_indicator.classification', $search);
            $filters[] = array('wg_customer_absenteeism_indicator.period', $search);
            $filters[] = array('wg_customer_absenteeism_indicator.workCenter', $search);
            $filters[] = array('dtype.item', $search);
            $filters[] = array('ctype.item', $search);
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_absenteeism_indicator.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, true, "");
    }

    public function getIndicators($indicatorId) {

        $query = "select 'Tasa de Accidentalidad' indicator, diseaseRate value, 0 goal from wg_customer_absenteeism_indicator where id = $indicatorId
union all

select 'Eventos', eventNumber
	, IFNULL((select targetEvent from wg_customer_absenteeism_indicator_target where period =  wg_customer_absenteeism_indicator.period limit 1),0)  goal
from wg_customer_absenteeism_indicator where id = $indicatorId

union all

select 'Dias Incapacitantes', disabilityDays
, IFNULL((select targetDay from wg_customer_absenteeism_indicator_target where period =  wg_customer_absenteeism_indicator.period limit 1),0)  goal
 from wg_customer_absenteeism_indicator where id = $indicatorId

union all

select 'Indice de Frecuencia (IF)', frequencyIndex
, IFNULL((select targetIF from wg_customer_absenteeism_indicator_target where period =  wg_customer_absenteeism_indicator.period limit 1),0)  goal
 from wg_customer_absenteeism_indicator where id = $indicatorId

union all

select 'Indice de Severidad (IS)', severityIndex
, IFNULL((select targetIS from wg_customer_absenteeism_indicator_target where period =  wg_customer_absenteeism_indicator.period limit 1),0)  goal
 from wg_customer_absenteeism_indicator where id = $indicatorId

union all

select 'Indice de Lesiones Incapacitantes (ILI)', disablingInjuriesIndex
,IFNULL((select targetILI from wg_customer_absenteeism_indicator_target where period =  wg_customer_absenteeism_indicator.period limit 1),0)   goal
 from wg_customer_absenteeism_indicator where id = $indicatorId
";


        $sql = $query;


        $results = DB::select($sql);

        return $results;
    }

    public function consolidate($customerId, $userId)
    {

        $query = "
INSERT INTO wg_customer_absenteeism_indicator
SELECT p.*
FROM
  ( SELECT NULL id,
                p.customer_id,
                cause,
                period,
                periodDate,
                p.workPlace,
                0 manHoursWorked,
                b.poblation,
                SUM(directCostTotal) directCostTotal,
                SUM(indirectCostTotal) indirectCostTotal,
                SUM(CASE WHEN type = 'Inicial' THEN 1 ELSE 0 END) eventNumber,
                0 targetEvent,
                0 diseaseRate,
                SUM(days) disabilityDays,
                0 targetDisabilityDays,
                0 targetFrequency,
                0 targetFrequencyIndex,
                0 frequencyIndex,
                0 targetSeverity,
                0 targetSeverityIndex,
                0 severityIndex,
                0 targetWorkAccident,
                0 disablingInjuriesIndex,
                $userId createdBy,
                NULL updatedBy,
                     NOW() created_at,
                     NULL updated_at
   FROM
     (
        SELECT
            cause,
            d.type,
            ce.workPlace,
            customer_id,
            calendar.days,
            calendar.period,
            calendar.`month`,
            calendar.`year`,
            CONCAT_WS('-',calendar.period,'01') periodDate,
            `start`,
            `end`,
            IFNULL(directCostTotal, 0) directCostTotal,
            IFNULL(indirectCostTotal, 0) indirectCostTotal
        FROM
            wg_customer_absenteeism_disability d
        INNER JOIN (
            SELECT
                wg_customer_absenteeism_disability.id AS customer_absenteeism_disability_id,
                EXTRACT(YEAR_MONTH FROM wg_calendar.full_date) period,
                YEAR(wg_calendar.full_date) AS `year`,
                MONTH(wg_calendar.full_date) AS `month`,
                COUNT(*) AS days
            FROM
                wg_customer_absenteeism_disability
            INNER JOIN wg_calendar ON wg_calendar.full_date BETWEEN DATE(wg_customer_absenteeism_disability.`start`)
            AND DATE(wg_customer_absenteeism_disability.`end`)
            GROUP BY
                EXTRACT(YEAR_MONTH FROM wg_calendar.full_date),
                wg_customer_absenteeism_disability.id
        ) calendar ON calendar.customer_absenteeism_disability_id = d.id
        INNER JOIN wg_customer_employee ce ON d.customer_employee_id = ce.id
      WHERE category = 'Incapacidad'
        AND cause IN ('AL',
                      'EG')
        AND ce.customer_id = :customer_id
        ) p
   INNER JOIN
     (SELECT count(*) poblation,
             workPlace,
             customer_id
      FROM wg_customer_employee
      GROUP BY customer_id,
               workPlace) b ON p.customer_id = b.customer_id
   AND p.workPlace = b.workPlace
   GROUP BY cause,
            YEAR,
            MONTH,
            period,
            p.workPlace) p
LEFT JOIN wg_customer_absenteeism_indicator i ON p.cause = i.classification
AND p.period = i.period
AND p.workPlace COLLATE utf8_general_ci = i.workCenter
AND i.customer_id = p.customer_id
WHERE i.id IS NULL
ORDER BY p.period DESC";

        DB::statement( $query, array(
            'customer_id' => $customerId
        ));


        //DAB->20181223: Sprint 3
        $currentPeriod = Carbon::now('America/Bogota')->format('Ym');
        $LastPeriod = Carbon::now('America/Bogota')->subMonth()->format('Ym');

        $query = "
UPDATE wg_customer_absenteeism_indicator i
INNER JOIN
  ( SELECT NULL id,
                p.customer_id,
                cause,
                period,
                periodDate,
                p.workPlace,
                0 manHoursWorked,
                b.poblation,
                SUM(directCostTotal) directCostTotal,
                SUM(indirectCostTotal) indirectCostTotal,
                SUM(CASE WHEN type = 'Inicial' THEN 1 ELSE 0 END) eventNumber,
                0 targetEvent,
                0 diseaseRate,
                SUM(days) disabilityDays,
                0 targetDisabilityDays,
                0 targetFrequency,
                0 targetFrequencyIndex,
                0 frequencyIndex,
                0 targetSeverity,
                0 targetSeverityIndex,
                0 severityIndex,
                0 targetWorkAccident,
                0 disablingInjuriesIndex,
                $userId createdBy,
                NULL updatedBy,
                     NOW() created_at,
                     NULL updated_at
   FROM
     (
        SELECT
            cause,
            d.type,
            ce.workPlace,
            customer_id,
            calendar.days,
            calendar.period,
            calendar.`month`,
            calendar.`year`,
            CONCAT_WS('-',calendar.period,'01') periodDate,
            `start`,
            `end`,
            IFNULL(directCostTotal, 0) directCostTotal,
            IFNULL(indirectCostTotal, 0) indirectCostTotal
        FROM
            wg_customer_absenteeism_disability d
        INNER JOIN (
            SELECT
                wg_customer_absenteeism_disability.id AS customer_absenteeism_disability_id,
                EXTRACT(YEAR_MONTH FROM wg_calendar.full_date) period,
                YEAR(wg_calendar.full_date) AS `year`,
                MONTH(wg_calendar.full_date) AS `month`,
                COUNT(*) AS days
            FROM
                wg_customer_absenteeism_disability
            INNER JOIN wg_calendar ON wg_calendar.full_date BETWEEN DATE(wg_customer_absenteeism_disability.`start`)
            AND DATE(wg_customer_absenteeism_disability.`end`)
            GROUP BY
                EXTRACT(YEAR_MONTH FROM wg_calendar.full_date),
                wg_customer_absenteeism_disability.id
        ) calendar ON calendar.customer_absenteeism_disability_id = d.id
        INNER JOIN wg_customer_employee ce ON d.customer_employee_id = ce.id
      WHERE category = 'Incapacidad'
        AND cause IN ('AL',
                      'EG')
        AND ce.customer_id = :customer_id) p
   INNER JOIN
     (SELECT count(*) poblation,
             workPlace,
             customer_id
      FROM wg_customer_employee
      GROUP BY customer_id,
               workPlace) b ON p.customer_id = b.customer_id
   AND p.workPlace = b.workPlace
   GROUP BY cause,
            YEAR,
            MONTH,
            period,
            p.workPlace) p ON p.cause = i.classification
AND p.period = i.period
AND p.workPlace COLLATE utf8_general_ci = i.workCenter
AND i.customer_id = p.customer_id

SET
       -- i.population = p.poblation,
       i.directCost = p.directCostTotal,
       i.indirectCost =  p.indirectCostTotal,
       i.eventNumber = p.eventNumber,
       i.disabilityDays = p.disabilityDays
WHERE i.id IS NOT NULL AND i.period IN ('$currentPeriod', '$LastPeriod')
";

        DB::statement( $query, array(
            'customer_id' => $customerId
        ));
    }
}
