<?php

namespace Wgroup\CustomerOccupationalReportIncident;

use DB;
use Exception;
use Log;
use Str;

class CustomerOccupationalReportIncidentService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new CustomerOccupationalReportIncident();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerAbsenteeismDisabilityRepository = new CustomerOccupationalReportIncidentRepository($model);

        if ($perPage > 0) {
            $this->customerAbsenteeismDisabilityRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_occupational_report_incident.id',
            'wg_customer_occupational_report_incident.type',
            'wg_customer_occupational_report_incident.cause',
            'wg_employee.firstName',
            'wg_employee.lastName',
            'wg_customer_occupational_report_incident.start',
            'wg_customer_occupational_report_incident.end'
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
            $this->customerAbsenteeismDisabilityRepository->sortBy('wg_customer_occupational_report_incident.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_occupational_report_incident.type', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_occupational_report_incident.start', $search);
            $filters[] = array('wg_customer_occupational_report_incident.end', $search);
            $filters[] = array('dtype.item', $search);
            $filters[] = array('ctype.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_occupational_report_incident.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_occupational_report_incident.status', '0');
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_occupational_report_incident.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllByEmployee($perPage = 10, $currentPage = 0,$customerId, $cause = "")
    {
        $startFrom = ($currentPage-1) * $perPage;

        $query = "select count(*) quantity, p.item cause, DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType
from wg_customer_occupational_report_incident cd
inner join wg_customer_employee ce on ce.id = cd.customer_employee_id
inner join wg_customers c on c.id = ce.customer_id
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes') p on cd.cause = p.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_type') dt on cd.type COLLATE utf8_general_ci = dt.value
left join (select * from system_parameters where `group` = 'employee_contract_type') ct on ce.contractType COLLATE utf8_general_ci = ct.value";

        $whereArray = array();

        $where = " where ce.customer_id = :customer_id";

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

    public function getSummaryDisability($perPage = 10, $currentPage = 0,$customerId, $cause = "")
    {
        $startFrom = ($currentPage-1) * $perPage;

        $query = "select count(*) quantity, p.item cause, DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType
from wg_customer_occupational_report_incident cd
inner join wg_customer_employee ce on ce.id = cd.customer_employee_id
inner join wg_customers c on c.id = ce.customer_id
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes') p on cd.cause = p.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_type') dt on cd.type COLLATE utf8_general_ci = dt.value
left join (select * from system_parameters where `group` = 'employee_contract_type') ct on ce.contractType COLLATE utf8_general_ci = ct.value";

        $whereArray = array();

        $where = " where ce.customer_id = :customer_id";

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
            from wg_customer_occupational_report_incident wgc
            inner join wg_customer_employee ce on ce.id = wgc.customer_employee_id";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.start) = :currentYear and ce.customer_id = :customer_id";

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
                from wg_customer_occupational_report_incident wgc
                inner join wg_customer_employee ce on ce.id = wgc.customer_employee_id
                where ce.customer_id = :customer_id
                order by 1 desc";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerOccupationalReportIncident();
        $this->customerAbsenteeismDisabilityRepository = new CustomerOccupationalReportIncidentRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_occupational_report_incident.type', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_occupational_report_incident.start', $search);
            $filters[] = array('wg_customer_occupational_report_incident.end', $search);
            $filters[] = array('dtype.item', $search);
            $filters[] = array('ctype.item', $search);
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_occupational_report_incident.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, true, "");
    }

    public function getOccupationalReportData($id)
    {
        $sql = "select
	cora.id,
	eps.item as eps,
	eps.code as eps_code,
	arl.item as arl,
	arl.code as arl_code,
	arl.id as arl_id,
	cora.is_afp,
	afp.item as afp,
	afp.code as afp_code,
	cora.customer_type_employment_relationship as employment_relationship,
	wg_economic_activity_customer.item as economic_activity_customer,
	wg_economic_activity_customer.code as economic_activity_customer_code,
	cora.customer_business_name,
	cora.customer_document_type,
	cora.customer_document_number,
	cora.customer_address,
	cora.customer_email,
	cora.customer_telephone,
	cora.customer_fax,
	usc.`name` customer_state,
	usc.`value` customer_state_code,
	tc.`name` customer_city,
	tc.`code` customer_city_code,
	cora.customer_zone,
	cora.is_customer_branch_same,
	wg_economic_activity_branch.item as economic_activity_branch,
	wg_economic_activity_branch.code as economic_activity_branch_code,
	cora.customer_branch_telephone,
	cora.customer_branch_address,
	cora.customer_branch_fax,
	usb.`name` customer_branch_state,
	usb.`value` customer_branch_state_code,
	tcb.`name` customer_branch_city,
	tcb.`code` customer_branch_city_code,
	cora.customer_branch_zone,
	cora.type_linkage,
	wg_type_linkage.code as type_linkage_code,
	cora.first_lastname,
	cora.second_lastname,
	cora.first_name,
	cora.second_name,
	cora.document_type,
	cora.document_number,
	DATE_FORMAT(cora.birthdate, '%d') birth_day,
	DATE_FORMAT(cora.birthdate, '%m') birth_month,
	YEAR(cora.birthdate) birth_year,
	cora.gender,
	cora.address,
	cora.telephone,
	cora.fax,
	us.`name` employee_state,
	us.`value` employee_state_code,
	t.`name` employee_city,
	t.`code` employee_city_code,
	cora.zone,
	ce.occupation as employee_occupation,
	employee_occupation.id as employee_occupation_code,
	cora.occupation_time_day,
	cora.occupation_time_month,
	jobdata.name job,
	DATE_FORMAT(cora.start_date, '%d') start_day,
	DATE_FORMAT(cora.start_date, '%m') start_month,
	YEAR(cora.start_date) start_year,
	cora.salary,
	cora.working_day,
	DATE_FORMAT(cora.accident_date, '%d') accident_day,
	DATE_FORMAT(cora.accident_date, '%m') accident_month,
	YEAR(cora.accident_date) accident_year,
	DATE_FORMAT(cora.accident_date, '%H') accident_hour,
	DATE_FORMAT(cora.accident_date, '%i') accident_minute,
	cora.accident_week_day,
	cora.accident_working_day,
	cora.accident_regular_work, -- Radio
	wg_report_regular_task.name as report_regular_task,
	wg_report_regular_task.id as report_regular_task_code,
	cora.accident_work_time,
	cora.accident_type,
	cora.accident_death_cause,
	usa.`name` accident_state,
	usa.`value` accident_state_code,
	tca.`name` accident_city,
	tca.`code` accident_city_code,
	cora.accident_zone,
	cora.accident_location,
	cora.accident_place,
	cora.accident_lesion_description,
	cora.accident_body_part_description,
	cora.accident_mechanism_description,
	cora.accident_description,
	DATE_FORMAT(cora.report_date, '%d') report_day,
	DATE_FORMAT(cora.report_date, '%m') report_month,
	YEAR(cora.report_date) report_year,
	cora.report_responsible_name,
	cora.report_responsible_document_type,
	cora.report_responsible_document_number,
	cora.report_responsible_job
from wg_customer_occupational_report_incident cora
inner join wg_customers c on cora.customer_id = c.id
inner join wg_employee e on cora.employee_id = e.id
inner join wg_customer_employee ce on ce.customer_id = c.id and ce.employee_id = e.id
left join (select * from system_parameters where `group` = 'wg_type_linkage') wg_type_linkage on cora.type_linkage COLLATE utf8_general_ci = wg_type_linkage.value
left join (select * from system_parameters where `group` = 'employee_document_type') tipodoc_employee on cora.document_type COLLATE utf8_general_ci = tipodoc_employee.value
left join (select * from system_parameters where `group` = 'gender') gender on cora.gender COLLATE utf8_general_ci = gender.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_employee on cora.zone COLLATE utf8_general_ci = wg_report_zone_employee.value
left join (select * from system_parameters where `group` = 'wg_report_regular_work') wg_report_regular_work_employee on cora.working_day COLLATE utf8_general_ci = wg_report_regular_work_employee.value
left join (select * from system_parameters where `group` = 'eps') eps on cora.eps COLLATE utf8_general_ci = eps.value
left join (select * from system_parameters where `group` = 'arl') arl on cora.arl COLLATE utf8_general_ci = arl.value
left join (select * from system_parameters where `group` = 'afp') afp on cora.afp COLLATE utf8_general_ci = afp.value
left join (select * from system_parameters where `group` = 'wg_report_employment_relationship') wg_report_employment_relationship on cora.customer_type_employment_relationship COLLATE utf8_general_ci = wg_report_employment_relationship.value
left join (select * from system_parameters where `group` = 'wg_economic_activity') wg_economic_activity_customer on cora.customer_economic_activity COLLATE utf8_general_ci = wg_economic_activity_customer.value
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_customer on cora.customer_document_type COLLATE utf8_general_ci = tipodoc_customer.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_customer on cora.customer_zone COLLATE utf8_general_ci = wg_report_zone_customer.value
left join (select * from system_parameters where `group` = 'wg_economic_activity') wg_economic_activity_branch on cora.customer_branch_economic__activity COLLATE utf8_general_ci = wg_economic_activity_branch.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_branch on cora.customer_branch_zone COLLATE utf8_general_ci = wg_report_zone_branch.value
left join (select * from system_parameters where `group` = 'wg_report_week_day') wg_report_week_day on cora.accident_week_day COLLATE utf8_general_ci = wg_report_week_day.value
left join (select * from system_parameters where `group` = 'wg_report_working_day') wg_report_working_day on cora.accident_working_day COLLATE utf8_general_ci = wg_report_working_day.value
left join (select ja.id, a.`name` from wg_customer_config_job_activity ja inner join wg_customer_config_activity a on ja.activity_id = a.id) wg_report_regular_task on cora.accident_regular_work_text = wg_report_regular_task.id
left join (select * from system_parameters where `group` = 'wg_report_accident_type') wg_report_accident_type on cora.accident_type COLLATE utf8_general_ci = wg_report_accident_type.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_accident on cora.accident_zone COLLATE utf8_general_ci = wg_report_zone_accident.value
left join (select * from system_parameters where `group` = 'wg_report_location') wg_report_location on cora.accident_location COLLATE utf8_general_ci = wg_report_location.value
left join (select * from system_parameters where `group` = 'wg_report_place') wg_report_place on cora.accident_place COLLATE utf8_general_ci = wg_report_place.value
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_responsible on cora.report_responsible_document_type COLLATE utf8_general_ci = tipodoc_responsible.value
left join rainlab_user_states us on us.id = cora.state_id
left join rainlab_user_states usc on usc.id = cora.customer_state_id
left join rainlab_user_states usb on usb.id = cora.customer_branch_state_id
left join rainlab_user_states usa on usa.id = cora.accident_state_id
left join wg_towns t on t.id = cora.city_id
left join wg_towns tc on tc.id = cora.customer_city_id
left join wg_towns tcb on tcb.id = cora.customer_branch_city_id
left join wg_towns tca on tca.id = cora.accident_city_id
left join wg_customer_config_job_data jobdata on cora.job = jobdata.id
left join wg_customer_config_job_activity employee_occupation on cora.occupation = employee_occupation.id
where cora.id = :id
";

        $results = DB::select( $sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getOccupationalReportLesionData($id)
    {
        $sql = "SELECT
		CONCAT('lesion_', REPLACE(lty.value, '.', '_'))  value
	, case when coral.lesion_id is not null then 1 else 0 end selected
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_lesion_type' ) lty
LEFT JOIN
  ( SELECT coral.lesion_id
   FROM wg_customer_occupational_report_incident_lesion coral
   INNER JOIN wg_customer_occupational_report_incident cora ON coral.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) coral ON lty. VALUE = coral.lesion_id COLLATE utf8_general_ci";

        $results = DB::select( $sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getOccupationalReportBodyData($id)
    {
        $sql = "SELECT
	CONCAT('body_', REPLACE(lty.value, '.', '_'))  value
	, case when corab.body_part_id is not null then 1 else 0 end selected
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_body_part' ) lty
LEFT JOIN
  ( SELECT corab.body_part_id
   FROM wg_customer_occupational_report_incident_body corab
   INNER JOIN wg_customer_occupational_report_incident cora ON corab.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) corab ON lty. VALUE = corab.body_part_id COLLATE utf8_general_ci";

        $results = DB::select( $sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getOccupationalReportFactorData($id)
    {
        $sql = "SELECT
	CONCAT('factor_', REPLACE(lty.value, '.', '_'))  value
	, case when coraf.factor_id is not null then 1 else 0 end selected
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_factor' ) lty
LEFT JOIN
  ( SELECT coraf.factor_id
   FROM wg_customer_occupational_report_incident_factor coraf
   INNER JOIN wg_customer_occupational_report_incident cora ON coraf.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) coraf ON lty. VALUE = coraf.factor_id COLLATE utf8_general_ci";

        $results = DB::select( $sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getOccupationalReportMechanismData($id)
    {
        $sql = "SELECT
	CONCAT('mechanism_', REPLACE(lty.value, '.', '_'))  value
	, case when coram.mechanism_id is not null then 1 else 0 end selected
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_mechanism' ) lty
LEFT JOIN
  ( SELECT coram.mechanism_id
   FROM wg_customer_occupational_report_incident_mechanism coram
   INNER JOIN wg_customer_occupational_report_incident cora ON coram.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) coram ON lty. VALUE = coram.mechanism_id COLLATE utf8_general_ci";

        $results = DB::select( $sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getOccupationalReportWitnessData($id)
    {
        $sql = "select
	w.name as witness_name,
	w.document_type as witness_document_type,
	w.document_number as witness_document_number,
	w.job as witness_job
from
wg_customer_occupational_report_incident_witness w
INNER JOIN wg_customer_occupational_report_incident cora ON w.customer_occupational_report_incident_id = cora.id
where cora.id = :id";

        $results = DB::select( $sql, array(
            'id' => $id
        ));

        return $results;
    }


    public function getOccupationalReportDataByCustomer($search, $perPage = 10, $currentPage = 0, $customerId = 0)
    {
        $startFrom = ($currentPage-1) * $perPage;

        $sql = "SELECT * FROM (
select
	cora.id,
	eps.item as eps,
	eps.code as eps_code,
	arl.item as arl,
	arl.code as arl_code,
	cora.is_afp,
	afp.item as afp,
	afp.code as afp_code,
	cora.customer_type_employment_relationship as employment_relationship,
	wg_economic_activity_customer.item as economic_activity_customer,
	wg_economic_activity_customer.code as economic_activity_customer_code,
	cora.customer_business_name,
	cora.customer_document_type,
	cora.customer_document_number,
	cora.customer_address,
	cora.customer_email,
	cora.customer_telephone,
	cora.customer_fax,
	usc.`name` customer_state,
	usc.`value` customer_state_code,
	tc.`name` customer_city,
	tc.`code` customer_city_code,
	cora.customer_zone,
	cora.is_customer_branch_same,
	wg_economic_activity_branch.item as economic_activity_branch,
	wg_economic_activity_branch.code as economic_activity_branch_code,
	cora.customer_branch_telephone,
	cora.customer_branch_address,
	cora.customer_branch_fax,
	usb.`name` customer_branch_state,
	usb.`value` customer_branch_state_code,
	tcb.`name` customer_branch_city,
	tcb.`code` customer_branch_city_code,
	cora.customer_branch_zone,
	cora.type_linkage,
	wg_type_linkage.code as type_linkage_code,
	cora.first_lastname lastName,
	cora.second_lastname,
	cora.first_name firstName,
	cora.second_name,
	tipodoc_employee.item documentType,
	cora.document_number documentNumber,
	DATE_FORMAT(cora.birthdate, '%d') birth_day,
	DATE_FORMAT(cora.birthdate, '%m') birth_month,
	YEAR(cora.birthdate) birth_year,
	cora.gender,
	cora.address,
	cora.telephone,
	cora.fax,
	us.`name` employee_state,
	us.`value` employee_state_code,
	t.`name` employee_city,
	t.`code` employee_city_code,
	cora.zone,
	ce.occupation as employee_occupation,
	employee_occupation.id as employee_occupation_code,
	cora.occupation_time_day,
	cora.occupation_time_month,
	jobdata.name job,
	DATE_FORMAT(cora.start_date, '%d') start_day,
	DATE_FORMAT(cora.start_date, '%m') start_month,
	YEAR(cora.start_date) start_year,
	cora.salary,
	cora.working_day,
	DATE_FORMAT(cora.accident_date, '%d') accident_day,
	DATE_FORMAT(cora.accident_date, '%m') accident_month,
	YEAR(cora.accident_date) accident_year,
	DATE_FORMAT(cora.accident_date, '%H') accident_hour,
	DATE_FORMAT(cora.accident_date, '%i') accident_minute,
	DATE_FORMAT(cora.accident_date, '%d/%m/%Y') accidentDate,
	cora.accident_week_day,
	cora.accident_working_day,
	cora.accident_regular_work, -- Radio
	wg_report_regular_task.name as report_regular_task,
	wg_report_regular_task.id as report_regular_task_code,
	cora.accident_work_time,
	cora.accident_type,
	cora.accident_death_cause,
	usa.`name` accident_state,
	usa.`value` accident_state_code,
	tca.`name` accident_city,
	tca.`code` accident_city_code,
	cora.accident_zone,
	cora.accident_location,
	cora.accident_place,
	cora.accident_lesion_description,
	cora.accident_body_part_description,
	cora.accident_mechanism_description,
	cora.accident_description,
	DATE_FORMAT(cora.report_date, '%d') report_day,
	DATE_FORMAT(cora.report_date, '%m') report_month,
	YEAR(cora.report_date) report_year,
	cora.report_responsible_name,
	cora.report_responsible_document_type,
	cora.report_responsible_document_number,
	cora.report_responsible_job,
	cora.status
from wg_customer_occupational_report_incident cora
inner join wg_customers c on cora.customer_id = c.id
inner join wg_employee e on cora.employee_id = e.id
inner join wg_customer_employee ce on ce.customer_id = c.id and ce.employee_id = e.id
left join (select * from system_parameters where `group` = 'wg_type_linkage') wg_type_linkage on cora.type_linkage COLLATE utf8_general_ci = wg_type_linkage.value
left join (select * from system_parameters where `group` = 'employee_document_type') tipodoc_employee on cora.document_type COLLATE utf8_general_ci = tipodoc_employee.value
left join (select * from system_parameters where `group` = 'gender') gender on cora.gender COLLATE utf8_general_ci = gender.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_employee on cora.zone COLLATE utf8_general_ci = wg_report_zone_employee.value
left join (select * from system_parameters where `group` = 'wg_report_regular_work') wg_report_regular_work_employee on cora.working_day COLLATE utf8_general_ci = wg_report_regular_work_employee.value
left join (select * from system_parameters where `group` = 'eps') eps on cora.eps COLLATE utf8_general_ci = eps.value
left join (select * from system_parameters where `group` = 'arl') arl on cora.arl COLLATE utf8_general_ci = arl.value
left join (select * from system_parameters where `group` = 'afp') afp on cora.afp COLLATE utf8_general_ci = afp.value
left join (select * from system_parameters where `group` = 'wg_report_employment_relationship') wg_report_employment_relationship on cora.customer_type_employment_relationship COLLATE utf8_general_ci = wg_report_employment_relationship.value
left join (select * from system_parameters where `group` = 'wg_economic_activity') wg_economic_activity_customer on cora.customer_economic_activity COLLATE utf8_general_ci = wg_economic_activity_customer.value
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_customer on cora.customer_document_type COLLATE utf8_general_ci = tipodoc_customer.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_customer on cora.customer_zone COLLATE utf8_general_ci = wg_report_zone_customer.value
left join (select * from system_parameters where `group` = 'wg_economic_activity') wg_economic_activity_branch on cora.customer_branch_economic__activity COLLATE utf8_general_ci = wg_economic_activity_branch.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_branch on cora.customer_branch_zone COLLATE utf8_general_ci = wg_report_zone_branch.value
left join (select * from system_parameters where `group` = 'wg_report_week_day') wg_report_week_day on cora.accident_week_day COLLATE utf8_general_ci = wg_report_week_day.value
left join (select * from system_parameters where `group` = 'wg_report_working_day') wg_report_working_day on cora.accident_working_day COLLATE utf8_general_ci = wg_report_working_day.value
left join (select ja.id, a.`name` from wg_customer_config_job_activity ja inner join wg_customer_config_activity a on ja.activity_id = a.id) wg_report_regular_task on cora.accident_regular_work_text = wg_report_regular_task.id
left join (select * from system_parameters where `group` = 'wg_report_accident_type') wg_report_accident_type on cora.accident_type COLLATE utf8_general_ci = wg_report_accident_type.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_accident on cora.accident_zone COLLATE utf8_general_ci = wg_report_zone_accident.value
left join (select * from system_parameters where `group` = 'wg_report_location') wg_report_location on cora.accident_location COLLATE utf8_general_ci = wg_report_location.value
left join (select * from system_parameters where `group` = 'wg_report_place') wg_report_place on cora.accident_place COLLATE utf8_general_ci = wg_report_place.value
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_responsible on cora.report_responsible_document_type COLLATE utf8_general_ci = tipodoc_responsible.value
left join rainlab_user_states us on us.id = cora.state_id
left join rainlab_user_states usc on usc.id = cora.customer_state_id
left join rainlab_user_states usb on usb.id = cora.customer_branch_state_id
left join rainlab_user_states usa on usa.id = cora.accident_state_id
left join wg_towns t on t.id = cora.city_id
left join wg_towns tc on tc.id = cora.customer_city_id
left join wg_towns tcb on tcb.id = cora.customer_branch_city_id
left join wg_towns tca on tca.id = cora.accident_city_id
left join wg_customer_config_job job on cora.job = job.id
left join wg_customer_config_job_data jobdata on job.job_id = jobdata.id
left join wg_customer_config_job_activity employee_occupation on cora.occupation = employee_occupation.id
where cora.customer_id = :id ) p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.id DESC ";

        $where = '';

        if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%' or p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.accidentDate like '%$search%')";
        }

        $sql = $sql.$where.$orderBy;
        $sql.=$limit;

        $results = DB::select( $sql, array(
            'id' => $customerId
        ));

        return $results;
    }

    public function getOccupationalReportDataByCustomerCount($search, $customerId = 0)
    {
        $sql = "SELECT * FROM (
select
	cora.id,
	eps.item as eps,
	eps.code as eps_code,
	arl.item as arl,
	arl.code as arl_code,
	cora.is_afp,
	afp.item as afp,
	afp.code as afp_code,
	cora.customer_type_employment_relationship as employment_relationship,
	wg_economic_activity_customer.item as economic_activity_customer,
	wg_economic_activity_customer.code as economic_activity_customer_code,
	cora.customer_business_name,
	cora.customer_document_type,
	cora.customer_document_number,
	cora.customer_address,
	cora.customer_email,
	cora.customer_telephone,
	cora.customer_fax,
	usc.`name` customer_state,
	usc.`value` customer_state_code,
	tc.`name` customer_city,
	tc.`code` customer_city_code,
	cora.customer_zone,
	cora.is_customer_branch_same,
	wg_economic_activity_branch.item as economic_activity_branch,
	wg_economic_activity_branch.code as economic_activity_branch_code,
	cora.customer_branch_telephone,
	cora.customer_branch_address,
	cora.customer_branch_fax,
	usb.`name` customer_branch_state,
	usb.`value` customer_branch_state_code,
	tcb.`name` customer_branch_city,
	tcb.`code` customer_branch_city_code,
	cora.customer_branch_zone,
	cora.type_linkage,
	wg_type_linkage.code as type_linkage_code,
	cora.first_lastname lastName,
	cora.second_lastname,
	cora.first_name firstName,
	cora.second_name,
	tipodoc_employee.item documentType,
	cora.document_number documentNumber,
	DATE_FORMAT(cora.birthdate, '%d') birth_day,
	DATE_FORMAT(cora.birthdate, '%m') birth_month,
	YEAR(cora.birthdate) birth_year,
	cora.gender,
	cora.address,
	cora.telephone,
	cora.fax,
	us.`name` employee_state,
	us.`value` employee_state_code,
	t.`name` employee_city,
	t.`code` employee_city_code,
	cora.zone,
	ce.occupation as employee_occupation,
	employee_occupation.id as employee_occupation_code,
	cora.occupation_time_day,
	cora.occupation_time_month,
	jobdata.name job,
	DATE_FORMAT(cora.start_date, '%d') start_day,
	DATE_FORMAT(cora.start_date, '%m') start_month,
	YEAR(cora.start_date) start_year,
	cora.salary,
	cora.working_day,
	DATE_FORMAT(cora.accident_date, '%d') accident_day,
	DATE_FORMAT(cora.accident_date, '%m') accident_month,
	YEAR(cora.accident_date) accident_year,
	DATE_FORMAT(cora.accident_date, '%H') accident_hour,
	DATE_FORMAT(cora.accident_date, '%i') accident_minute,
	DATE_FORMAT(cora.accident_date, '%d/%m/%Y') accidentDate,
	cora.accident_week_day,
	cora.accident_working_day,
	cora.accident_regular_work, -- Radio
	wg_report_regular_task.name as report_regular_task,
	wg_report_regular_task.id as report_regular_task_code,
	cora.accident_work_time,
	cora.accident_type,
	cora.accident_death_cause,
	usa.`name` accident_state,
	usa.`value` accident_state_code,
	tca.`name` accident_city,
	tca.`code` accident_city_code,
	cora.accident_zone,
	cora.accident_location,
	cora.accident_place,
	cora.accident_lesion_description,
	cora.accident_body_part_description,
	cora.accident_mechanism_description,
	cora.accident_description,
	DATE_FORMAT(cora.report_date, '%d') report_day,
	DATE_FORMAT(cora.report_date, '%m') report_month,
	YEAR(cora.report_date) report_year,
	cora.report_responsible_name,
	cora.report_responsible_document_type,
	cora.report_responsible_document_number,
	cora.report_responsible_job,
	cora.status
from wg_customer_occupational_report_incident cora
inner join wg_customers c on cora.customer_id = c.id
inner join wg_employee e on cora.employee_id = e.id
inner join wg_customer_employee ce on ce.customer_id = c.id and ce.employee_id = e.id
left join (select * from system_parameters where `group` = 'wg_type_linkage') wg_type_linkage on cora.type_linkage COLLATE utf8_general_ci = wg_type_linkage.value
left join (select * from system_parameters where `group` = 'employee_document_type') tipodoc_employee on cora.document_type COLLATE utf8_general_ci = tipodoc_employee.value
left join (select * from system_parameters where `group` = 'gender') gender on cora.gender COLLATE utf8_general_ci = gender.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_employee on cora.zone COLLATE utf8_general_ci = wg_report_zone_employee.value
left join (select * from system_parameters where `group` = 'wg_report_regular_work') wg_report_regular_work_employee on cora.working_day COLLATE utf8_general_ci = wg_report_regular_work_employee.value
left join (select * from system_parameters where `group` = 'eps') eps on cora.eps COLLATE utf8_general_ci = eps.value
left join (select * from system_parameters where `group` = 'arl') arl on cora.arl COLLATE utf8_general_ci = arl.value
left join (select * from system_parameters where `group` = 'afp') afp on cora.afp COLLATE utf8_general_ci = afp.value
left join (select * from system_parameters where `group` = 'wg_report_employment_relationship') wg_report_employment_relationship on cora.customer_type_employment_relationship COLLATE utf8_general_ci = wg_report_employment_relationship.value
left join (select * from system_parameters where `group` = 'wg_economic_activity') wg_economic_activity_customer on cora.customer_economic_activity COLLATE utf8_general_ci = wg_economic_activity_customer.value
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_customer on cora.customer_document_type COLLATE utf8_general_ci = tipodoc_customer.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_customer on cora.customer_zone COLLATE utf8_general_ci = wg_report_zone_customer.value
left join (select * from system_parameters where `group` = 'wg_economic_activity') wg_economic_activity_branch on cora.customer_branch_economic__activity COLLATE utf8_general_ci = wg_economic_activity_branch.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_branch on cora.customer_branch_zone COLLATE utf8_general_ci = wg_report_zone_branch.value
left join (select * from system_parameters where `group` = 'wg_report_week_day') wg_report_week_day on cora.accident_week_day COLLATE utf8_general_ci = wg_report_week_day.value
left join (select * from system_parameters where `group` = 'wg_report_working_day') wg_report_working_day on cora.accident_working_day COLLATE utf8_general_ci = wg_report_working_day.value
left join (select ja.id, a.`name` from wg_customer_config_job_activity ja inner join wg_customer_config_activity a on ja.activity_id = a.id) wg_report_regular_task on cora.accident_regular_work_text = wg_report_regular_task.id
left join (select * from system_parameters where `group` = 'wg_report_accident_type') wg_report_accident_type on cora.accident_type COLLATE utf8_general_ci = wg_report_accident_type.value
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_accident on cora.accident_zone COLLATE utf8_general_ci = wg_report_zone_accident.value
left join (select * from system_parameters where `group` = 'wg_report_location') wg_report_location on cora.accident_location COLLATE utf8_general_ci = wg_report_location.value
left join (select * from system_parameters where `group` = 'wg_report_place') wg_report_place on cora.accident_place COLLATE utf8_general_ci = wg_report_place.value
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_responsible on cora.report_responsible_document_type COLLATE utf8_general_ci = tipodoc_responsible.value
left join rainlab_user_states us on us.id = cora.state_id
left join rainlab_user_states usc on usc.id = cora.customer_state_id
left join rainlab_user_states usb on usb.id = cora.customer_branch_state_id
left join rainlab_user_states usa on usa.id = cora.accident_state_id
left join wg_towns t on t.id = cora.city_id
left join wg_towns tc on tc.id = cora.customer_city_id
left join wg_towns tcb on tcb.id = cora.customer_branch_city_id
left join wg_towns tca on tca.id = cora.accident_city_id
left join wg_customer_config_job job on cora.job = job.id
left join wg_customer_config_job_data jobdata on job.job_id = jobdata.id
left join wg_customer_config_job_activity employee_occupation on cora.occupation = employee_occupation.id
where cora.customer_id = :id ) p";

        $orderBy = " ORDER BY p.id DESC ";

        $where = '';

        if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%' or p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.accidentDate like '%$search%')";
        }

        $sql = $sql.$where.$orderBy;

        $results = DB::select( $sql, array(
            'id' => $customerId
        ));

        return count($results);
    }

    public function getYearFilter($reportId) {

        $query = "SELECT
	DISTINCT 0 id, YEAR(o.`accident_date`) item, YEAR(o.`accident_date`) `value`
FROM
	wg_customer_occupational_report_incident o
WHERE customer_id = :id
ORDER BY o.`accident_date` DESC";

        $results = DB::select( $query, array(
            'id' => $reportId
        ));

        return $results;
    }

    public function getAllSummaryByLesion($customerId, $year) {

        $query = "select  p.`id`, lty.item `name`, lty.item abbreviation
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_lesion p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.lesion_id";


        $results = DB::select( $query, array(
            'customer_id' => $customerId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByLesionExport($customerId, $year) {

        $query = "select  lty.item `Nombre`, lty.item `Abreviacion`
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_lesion p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.lesion_id";


        $results = DB::select( $query, array(
            'customer_id' => $customerId,
            'year' => $year
        ));


        return $results;
    }

    public function getDashboardPieAccidentType($customerId, $year)
    {
        $sql = "select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
WHERE customer_id = :customer_id and YEAR(accident_date) = :year
group by customer_id, YEAR(accident_date), accident_type";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardPieDeathCause($customerId, $year)
    {
        $sql = "select count(*) value,  'SI' label, '#F7464A' highlight, '#F7464A' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and accident_death_cause = 1
group by customer_id, YEAR(accident_date)

union ALL

select count(*) value,  'NO' label, '#FDB45C' highlight, '#FFC870' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
WHERE customer_id = :customer_id_2 and YEAR(accident_date) = :year_2 and accident_death_cause = 0
group by customer_id, YEAR(accident_date)";

        $results = DB::select( $sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getDashboardPieLocation($customerId, $year)
    {
        $sql = "select count(*) value,  lty.item label, '#F7464A' highlight, '#FF5A5E' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
WHERE customer_id = :customer_id and YEAR(accident_date) = :year
group by customer_id, YEAR(accident_date), accident_location";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardBarLink($customerId, $year)
    {
        $sql = "select  o.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = o.customer_type_employment_relationship COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by o.customer_type_employment_relationship";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardWorkTime($customerId, $year)
    {
        $sql = "select  o.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = o.accident_regular_work
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by o.accident_regular_work";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardWeekDay($customerId, $year)
    {
        $sql = "select  o.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = o.accident_week_day COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by o.accident_week_day";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardPlace($customerId, $year)
    {
        $sql = "select  o.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_place' ) lty ON lty.value = o.accident_place COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by o.accident_place";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardLesion($customerId, $year)
    {
        $sql = "select  p.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_lesion p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.lesion_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardBody($customerId, $year)
    {
        $sql = "select  p.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_body p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.body_part_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardFactor($customerId, $year)
    {
        $sql = "select  p.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_factor p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.factor_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }
}
