<?php

namespace Wgroup\CustomerOccupationalInvestigationAl;

use DB;
use Exception;
use Log;
use Str;


class CustomerOccupationalInvestigationAlService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerInvestigationAlRepository;

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
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0)
    {

        $model = new CustomerOccupationalInvestigationAl();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerInvestigationAlRepository = new CustomerOccupationalInvestigationAlRepository($model);

        if ($perPage > 0) {
            $this->customerInvestigationAlRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_occupational_investigation_al.id',
            'wg_customer_occupational_investigation_al.accidentDate',
            'wg_customers.businessName',
            'wg_customer_document_type.value',
            'wg_customers.documentNumber',
            'wg_employee.firstName',
            'wg_employee.lastName',
            'wg_employee.fullName',
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
                    $this->customerInvestigationAlRepository->sortBy($colName, $dir);
                } else {
                    $this->customerInvestigationAlRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerInvestigationAlRepository->sortBy('wg_customer_occupational_investigation_al.id', 'desc');
        }

        $filters = array();

		$filters[] = array('wg_customer_employee.customer_id', $customerId);

		if (strlen(trim($search)) > 0) {
			$filters[] = array('wg_customer_occupational_investigation_al.accidentDate', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customer_document_type.item', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_employee.fullName', $search);
			$filters[] = array('investigation_accident_type.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_occupational_investigation_al.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_occupational_investigation_al.status', '0');
        }

        $this->customerInvestigationAlRepository->setColumns(['wg_customer_occupational_investigation_al.*']);

        return $this->customerInvestigationAlRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId = 0)
    {

        $model = new CustomerOccupationalInvestigationAl();
        $this->customerInvestigationAlRepository = new CustomerOccupationalInvestigationAlRepository($model);

        $filters = array();

		$filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_occupational_investigation_al.accidentDate', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customer_document_type.item', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_employee.fullName', $search);
            $filters[] = array('investigation_accident_type.item', $search);
        }

        $this->customerInvestigationAlRepository->setColumns(['wg_customer_occupational_investigation_al.*']);

        return $this->customerInvestigationAlRepository->getFilteredsOptional($filters, true, "");
    }

    public function getInvestigationReportDataPartOne($id)
    {
        $sql = "select
	cial.id,
	c.documentNumber customerDocumentNumber,
	c.businessName customerBusinessName,
	tipodoc_employee.item employeeDocumentType,
	e.documentNumber employeeDocumentNumber,
	e.firstName employeeFirstName,
	e.lastName employeeLastName,

	cial.accidentDate,
	cial.notificationDate,
	investigation_notified_by.item notifiedBy,
	investigation_accident_type.item accidentType,
	UPPER(uci.`name`) country,
	UPPER(usi.`name`) state,
	UPPER(ti.`name`) city,
	investigation_dx_resolution.item dxResolution,
	investigation_hazard_type.item hazardType,
	agent_document_type.item agentDocumentType,
	agent.documentNumber agentDocumentNumber,
	agent.firstName agentFirstName,
	agent.lastName agentLastName,
	director_document_type.item directorDocumentType,
	director.documentNumber directorDocumentNumber,
	director.firstName directorFirstName,
	director.lastName directorLastName,
	investigation_intervention_plan.item interventionPlan,
	cial.sisalud,
	cial.injury,
	cial.observation,
	investigator_document_type.item investigatorDocumentType,
	investigator.documentNumber investigatorDocumentNumber,
	investigator.firstName investigatorFirstName,
	investigator.lastName investigatorLastName,
	cial.hireDate,
	cial.`schedule`,
	cial.sequence,
	cial.customerObservation,
	wg_economic_activity_customer.code as customerPrincipalEconomicActivityCode,
	wg_economic_activity_customer.name as customerPrincipalEconomicActivityName,
	cial.customerPrincipalRiskClass,
	UPPER(ucc.`name`) customerPrincipalCountry,
	UPPER(usc.`name`) customerPrincipalSate,
	UPPER(tc.`name`) customerPrincipalCity,
	wg_report_zone_customer.item customerPrincipalZone,
	customerAddress.`value` customerPrincipalAddress,
	customerTel.`value` customerPrincipalTel,
	customerEmail.`value` customerPrincipalEmail,
	cial.customerResponsibleHealth,
	wg_economic_activity_branch.code as customerBranchEconomicActivityCode,
	wg_economic_activity_branch.name as customerBranchEconomicActivityName,
	cial.customerBranchRiskClass,
	UPPER(ucb.`name`) customerBranchCountry,
	UPPER(usb.`name`) customerBranchState,
	UPPER(tcb.`name`) customerBranchCity,
	wg_report_zone_branch.item as customerBranchZone,
	customerBranchAddress.`value` customerBranchAddress,
	customerBranchTel.`value` customerBranchTel,
	customerBranchEmail.`value` customerBranchEmail,
	e.birthdate employeeBirthDate,
	DATE_FORMAT(e.birthdate, '%d') birth_day,
	DATE_FORMAT(e.birthdate, '%m') birth_month,
	YEAR(e.birthdate) birth_year,
	gender.item employeeGender,
	investigation_employee_link_type.item as employeeLinkType,
	UPPER(uce.`name`) employeeCountry,
	UPPER(us.`name`) employeeState,
	UPPER(t.`name`) employeeCity,
	wg_report_zone_employee.item as employeeZone,
	employeeAddress.`value` employeeAddress,
	employeeTel.`value` employeeTel,
	employeeEmail.`value` employeeEmail,
	cial.employeeHabitualOccupation,
	cial.employeeJobTask,
	cial.employeeHabitualOccupationTime,
	cial.employeeStartDate,
	DATE_FORMAT(cial.employeeStartDate, '%d') start_day,
	DATE_FORMAT(cial.employeeStartDate, '%m') start_month,
	YEAR(cial.employeeStartDate) start_year,
	cial.employeeDuration,
	cial.employeeRegularWork,
	CASE WHEN employeeIsMissionWorker = 1 THEN 'SI' ELSE 'NO' END employeeIsMissionWorker,
	cial.employeeMissionCompanyName,
	cial.employeeMissionSalary,
	investigation_employee_mission_working_day.item employeeMissionWorkingDay,
	eps.code as eps_code,
	eps.item as employeeEps,
	arl.code as arl_code,
	arl.item as employeeArl,
	afp.code as afp_code,
	afp.item as employeeAfp,
	cial.employeeClarification,
	cial.accidentDateOf,
	DATE_FORMAT(cial.accidentDateOf, '%d') accident_day,
	DATE_FORMAT(cial.accidentDateOf, '%m') accident_month,
	YEAR(cial.accidentDateOf) accident_year,
	DATE_FORMAT(cial.accidentDateOf, '%H') accident_hour,
	DATE_FORMAT(cial.accidentDateOf, '%i') accident_minute,
	wg_report_working_day.item accidentWorkingDay,
	diagnostic_accident_status.item accidentIsRegularWork,
	cial.accidentOtherRegularWorkText,
	cial.accidentWorkTimeHour,
	cial.accidentWorkTimeMinute,
	investigation_accident_category.item accidentCategory,
	accident_death_cause.item accidentIsDeathCause,
	UPPER(uci.`name`) accidentCountry,
	UPPER(usa.`name`) accidentState,
	UPPER(tca.`name`) accidentCity,
	wg_report_zone_accident.item accidentZone,
	investigation_accident_place.item accidentPlace,
	cial.toWhom,
	cial.toWhomJob,
	cial.agrResponsible,
	cial.riskManager
from wg_customer_occupational_investigation_al cial
inner join wg_customers c on cial.customer_id = c.id
inner join wg_customer_employee ce on ce.id = cial.customer_employee_id
inner join wg_employee e on ce.employee_id = e.id
left join (select * from system_parameters where `group` = 'investigation_notified_by') investigation_notified_by on cial.notifiedBy COLLATE utf8_general_ci = investigation_notified_by.value
left join (select * from system_parameters where `group` = 'investigation_accident_type') investigation_accident_type on cial.accidentType COLLATE utf8_general_ci = investigation_accident_type.value
left join (select * from system_parameters where `group` = 'investigation_dx_resolution') investigation_dx_resolution on cial.dxResolution COLLATE utf8_general_ci = investigation_dx_resolution.value
left join (select * from system_parameters where `group` = 'investigation_hazard_type') investigation_hazard_type on cial.hazardType COLLATE utf8_general_ci = investigation_hazard_type.value
left join (select * from system_parameters where `group` = 'investigation_accident_place') investigation_accident_place on cial.accidentPlace COLLATE utf8_general_ci = investigation_accident_place.value
left join (select * from wg_agent) agent on cial.agent_id = agent.id
left join (select * from system_parameters where `group` = 'tipodoc') agent_document_type on agent.documentType COLLATE utf8_general_ci = agent_document_type.value
left join (select * from wg_agent) director on cial.director_id = director.id
left join (select * from system_parameters where `group` = 'tipodoc') director_document_type on director.documentType COLLATE utf8_general_ci = director_document_type.value
left join (select * from system_parameters where `group` = 'investigation_intervention_plan') investigation_intervention_plan on cial.interventionPlan COLLATE utf8_general_ci = investigation_intervention_plan.value
left join (select * from wg_agent) investigator on cial.investigator_id = investigator.id
left join (select * from system_parameters where `group` = 'tipodoc') investigator_document_type on investigator.documentType COLLATE utf8_general_ci = investigator_document_type.value
-- CUSTOMER
LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\Models\Customer' AND type = 'email'
						GROUP BY entityId, entityName, type
					) customerEmail  ON customerEmail.entityId = c.id

LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\Models\Customer' AND type = 'tel'
						GROUP BY entityId, entityName, type
					) customerTel  ON customerTel.entityId = c.id


LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\Models\Models' AND type = 'dir'
						GROUP BY entityId, entityName, type
					) customerAddress  ON customerAddress.entityId = c.id
-- BRANCH
LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\CustomerOccupationalInvestigationAl\CustomerOccupationalInvestigationAl' AND type = 'email'
						GROUP BY entityId, entityName, type
					) customerBranchEmail  ON customerBranchEmail.entityId = cial.id

LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\CustomerOccupationalInvestigationAl\CustomerOccupationalInvestigationAl' AND type = 'tel'
						GROUP BY entityId, entityName, type
					) customerBranchTel  ON customerBranchTel.entityId = cial.id

LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\CustomerOccupationalInvestigationAl\CustomerOccupationalInvestigationAl' AND type = 'dir'
						GROUP BY entityId, entityName, type
					) customerBranchAddress  ON customerBranchAddress.entityId = cial.id

-- EMPLOYEE
LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\Employee\Employee' AND type = 'email'
						GROUP BY entityId, entityName, type
					) employeeEmail  ON employeeEmail.entityId = e.id

LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\Employee\Employee' AND type = 'tel'
						GROUP BY entityId, entityName, type
					) employeeTel  ON employeeTel.entityId = e.id

LEFT JOIN (
						SELECT MIN(`value`) `value`, entityId, entityName FROM wg_info_detail
						WHERE entityName = 'Wgroup\Employee\Employee' AND type = 'dir'
						GROUP BY entityId, entityName, type
					) employeeAddress  ON employeeAddress.entityId = e.id

left join (select * from system_parameters where `group` = 'investigation_employee_link_type') investigation_employee_link_type on cial.employeeLinkType COLLATE utf8_general_ci = investigation_employee_link_type.value
left join (select * from system_parameters where `group` = 'investigation_employee_mission_working_day') investigation_employee_mission_working_day on cial.employeeMissionWorkingDay COLLATE utf8_general_ci = investigation_employee_mission_working_day.value
left join (select * from system_parameters where `group` = 'diagnostic_accident_status') diagnostic_accident_status on cial.accidentIsRegularWork COLLATE utf8_general_ci = diagnostic_accident_status.value
left join (select * from system_parameters where `group` = 'investigation_accident_category') investigation_accident_category on cial.accidentCategory COLLATE utf8_general_ci = investigation_accident_category.value
left join (select * from system_parameters where `group` = 'diagnostic_accident_status') accident_death_cause on cial.accidentIsDeathCause COLLATE utf8_general_ci = accident_death_cause.value
-- done
left join (select * from system_parameters where `group` = 'employee_document_type') tipodoc_employee on e.documentType COLLATE utf8_general_ci = tipodoc_employee.value
-- done
left join (select * from system_parameters where `group` = 'gender') gender on e.gender COLLATE utf8_general_ci = gender.value
-- done
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_employee on cial.employeeZone COLLATE utf8_general_ci = wg_report_zone_employee.value
-- done
left join (select * from system_parameters where `group` = 'eps') eps on e.eps COLLATE utf8_general_ci = eps.value
-- done
left join (select * from system_parameters where `group` = 'arl') arl on e.arl COLLATE utf8_general_ci = arl.value
-- done
left join (select * from system_parameters where `group` = 'afp') afp on e.afp COLLATE utf8_general_ci = afp.value
-- done
left join (select * from wg_investigation_economic_activity) wg_economic_activity_customer on cial.customerPrincipalEconomicActivity = wg_economic_activity_customer.id
left join (select * from wg_investigation_economic_activity) wg_economic_activity_branch on cial.customerBranchEconomicActivity = wg_economic_activity_branch.id
-- done
left join (select * from system_parameters where `group` = 'tipodoc') tipodoc_customer on c.documentType = tipodoc_customer.value
-- done
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_customer on cial.customerPrincipalZone COLLATE utf8_general_ci = wg_report_zone_customer.value
-- done
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_branch on cial.customerBranchZone COLLATE utf8_general_ci = wg_report_zone_branch.value
-- done
left join (select * from system_parameters where `group` = 'wg_report_working_day') wg_report_working_day on cial.accidentWorkingDay COLLATE utf8_general_ci = wg_report_working_day.value
-- done
left join (select * from system_parameters where `group` = 'wg_report_accident_type') wg_report_accident_type on cial.accidentType COLLATE utf8_general_ci = wg_report_accident_type.value
-- done
left join (select * from system_parameters where `group` = 'wg_report_zone') wg_report_zone_accident on cial.accidentZone COLLATE utf8_general_ci = wg_report_zone_accident.value
-- done
left join (select * from system_parameters where `group` = 'investigation_accident_place') wg_report_place on cial.accidentPlace COLLATE utf8_general_ci = wg_report_place.value
left join rainlab_user_countries uci on uci.id = cial.country_id

left join rainlab_user_countries uca on uca.id = cial.accident_country_id
left join rainlab_user_countries uce on uce.id = e.country_id
left join rainlab_user_countries ucc on ucc.id = c.country_id
left join rainlab_user_countries ucb on ucb.id = cial.customer_branch_country_id

left join rainlab_user_states usi on usi.id = cial.state_id
left join rainlab_user_states us on us.id = e.state_id
left join rainlab_user_states usc on usc.id = c.state_id
left join rainlab_user_states usb on usb.id = cial.customer_branch_state_id
left join rainlab_user_states usa on usa.id = cial.accident_state_id

left join wg_towns ti on ti.id = cial.city_id
left join wg_towns t on t.id = e.city_id
left join wg_towns tc on tc.id = c.city_id
left join wg_towns tcb on tcb.id = cial.customer_branch_city_id
left join wg_towns tca on tca.id = cial.accident_city_id

where cial.id = :id
";

        $results = DB::select($sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getInvestigationReportDataPartTwo($id)
    {
        $sql = "select
	jobdata.name employeeJob,
	investigation_accident_injury_type.item accidentInjuryType,
	cial.accidentInjuryTypeText,
	investigation_accident_body_part.item accidentBodyPart,
	investigation_accident_agent.item accidentAgent,
	investigation_accident_mechanism.item accidentMechanism,
	case when accidentCompanyTransport = 1 THEN 'ransporte por medio de la empresa' else 'Auto transporte'   end accidentTransportType,
	cial.accidentReportDate,
	cial.accidentReportMadeBy,
	cial.accidentReportJob,
	cial.accidentReportClarification,
	cial.eventObservation,
	eventLocation.description eventLocation,
	eventVersion.description eventVersion,
	eventCondition.description eventCondition,
	eventDocument.description eventDocument,
	insecureAct.`code` insecureActCode,
	insecureAct.`name` insecureActDescription,
	ciac.insecureActObservation,
	insecureCondition.`code` insecureConditionCode,
	insecureCondition.`name` insecureConditionDescription,
	ciac.insecureConditionObservation,
	workFactor.`code` workFactorCode,
	workFactor.`name` workFactorDescription,
	ciac.workFactorObservation,
	personalFactor.`code` personalFactorCode,
	personalFactor.`name` personalFactorDescription,
	ciac.personalFactorObservation,
	cial.checkDate,
	cial.place,
	cial.address,
	cial.realizedBy,
	cial.reviewedBy
from wg_customer_occupational_investigation_al cial
inner join wg_customers c on cial.customer_id = c.id
inner join wg_customer_employee ce on ce.id = cial.customer_employee_id
inner join wg_employee e on ce.employee_id = e.id
left join (select * from system_parameters where `group` = 'investigation_accident_injury_type') investigation_accident_injury_type on cial.accidentInjuryType COLLATE utf8_general_ci = investigation_accident_injury_type.value
left join (select * from system_parameters where `group` = 'investigation_accident_body_part') investigation_accident_body_part on cial.accidentBodyPart COLLATE utf8_general_ci = investigation_accident_body_part.value
left join (select * from system_parameters where `group` = 'investigation_accident_agent') investigation_accident_agent on cial.accidentAgent COLLATE utf8_general_ci = investigation_accident_agent.value
left join (select * from system_parameters where `group` = 'investigation_accident_mechanism') investigation_accident_mechanism on cial.accidentMechanism COLLATE utf8_general_ci = investigation_accident_mechanism.value
left join (select * from wg_customer_occupational_investigation_al_event where `type` = 'location') eventLocation on cial.id = eventLocation.customer_investigation_id
left join (select * from wg_customer_occupational_investigation_al_event where `type` = 'version') eventVersion on cial.id = eventVersion.customer_investigation_id
left join (select * from wg_customer_occupational_investigation_al_event where `type` = 'condition') eventCondition on cial.id = eventCondition.customer_investigation_id
left join (select * from wg_customer_occupational_investigation_al_event where `type` = 'document') eventDocument on cial.id = eventDocument.customer_investigation_id
left join wg_customer_occupational_investigation_al_cause ciac on ciac.customer_investigation_id = cial.id
left join wg_investigation_cause insecureAct on insecureAct.id = ciac.insecureAct
left join wg_investigation_cause insecureCondition on insecureCondition.id = ciac.insecureCondition
left join wg_investigation_cause workFactor on workFactor.id = ciac.workFactor
left join wg_investigation_cause personalFactor on personalFactor.id = ciac.personalFactor
left join wg_customer_config_job job on ce.job = job.id
left join wg_customer_config_job_data jobdata on job.job_id = jobdata.id
where cial.id = :id
";

        $results = DB::select($sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getInvestigationReportDocuments($id)
    {
        $sql = "select
	investigation_document_type.item type,
	ciad.description,
	ciad.folio
from wg_customer_occupational_investigation_al cial
inner join wg_customer_occupational_investigation_al_document ciad on ciad.customer_investigation_id = cial.id
left join (select * from system_parameters where `group` = 'investigation_document_type') investigation_document_type on ciad.type COLLATE utf8_general_ci = investigation_document_type.value
where cial.id = :id
";

        $results = DB::select($sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getInvestigationReportMeasures($id)
    {
        $sql = "select
	investigation_measure.item type,
	ciam.description,
	ciam.responsible,
	ciam.checkDate
from wg_customer_occupational_investigation_al cial
inner join wg_customer_occupational_investigation_al_measure ciam on ciam.customer_investigation_id = cial.id
left join (select * from system_parameters where `group` = 'investigation_measure') investigation_measure on ciam.type COLLATE utf8_general_ci = investigation_measure.value
where cial.id = :id
";

        $results = DB::select($sql, array(
            'id' => $id
        ));

        return $results;
    }

    public function getInvestigationReportFactors($id)
    {
        $sql = "select
	investigation_factor.item type,
	ciaf.cause,
	ciaf.sort
from wg_customer_occupational_investigation_al cial
inner join wg_customer_occupational_investigation_al_factor ciaf on ciaf.customer_investigation_id = cial.id
left join (select * from system_parameters where `group` = 'investigation_factor') investigation_factor on ciaf.factor COLLATE utf8_general_ci = investigation_factor.value
where cial.id = :id
";

        $results = DB::select($sql, array(
            'id' => $id
        ));

        return $results;
    }

    //-------------------------------------------------------------------START REVIEW
    public function getInvestigationYearFilter()
    {

        $query = "SELECT
	DISTINCT 0 id, YEAR(o.`accidentDateOf`) item, YEAR(o.`accidentDateOf`) `value`
FROM
	wg_customer_occupational_investigation_al o
ORDER BY o.`accidentDateOf` DESC";

        $results = DB::select($query);

        return $results;
    }

    public function getInvestigationCustomerFilter()
    {

        $query = "SELECT
	c.`id`, c.`businessName` item, c.`id` `value`
FROM
	wg_customer_occupational_investigation_al o
INNER JOIN wg_customers c on c.id = o.customer_id
GROUP BY c.`id`
ORDER BY c.businessName";

        $results = DB::select($query);

        return $results;
    }

    public function getAllReviewInjury($customerId, $year)
    {
		$sql = "SELECT o.`id`,
				o.customer_id,
				d.item `name`,
				d.`item` abbreviation,
				'#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_injury_type') d ON d.`value` = o.accidentInjuryType COLLATE utf8_general_ci";

		$where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentInjuryType";

		$query = $sql.$where->sql.$groupBy;

		$results = DB::select( $query, $where->filters );

		return $results;
    }

    public function getAllReviewInjuryExport($customerId, $year)
    {

		$sql = "SELECT
      		d.item `Lesión`,
      		d.`item` Abreviacióm
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_injury_type') d ON d.`value` = o.accidentInjuryType COLLATE utf8_general_ci";

		$where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentInjuryType";

		$query = $sql.$where->sql.$groupBy;

		$results = DB::select( $query, $where->filters );

		return $results;
    }

    //--------------------------------CHARTS

    public function getDashboardBarEconomyActivity($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       d.`name`,
       d.`name` abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_investigation_economic_activity d ON d.id = o.customerPrincipalEconomicActivity";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.customerPrincipalEconomicActivity";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarLink($customerId, $year)
    {
        $sql = "SELECT o.`id`,
		o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_employee_link_type') d ON d.`value` = o.employeeLinkType COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.employeeLinkType";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarGender($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       d.item `name`,
       d.`item` abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_customer_employee ce ON ce.id = o.customer_employee_id
INNER JOIN wg_employee e ON e.id = ce.employee_id
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'gender') d ON d.`value` = e.gender COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY e.gender";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarAccidentState($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       UPPER(d.`name`) `name`,
       UPPER(d.`name`) abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN rainlab_user_states d ON d.id = o.accident_state_id";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accident_state_id";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarAccidentCity($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       UPPER(d.`name`) `name`,
       UPPER(d.`name`) abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_towns d ON d.id = o.accident_city_id";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accident_city_id";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarRegularWork($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'diagnostic_accident_status') d ON d.`value` = o.accidentIsRegularWork COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentIsRegularWork";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarWorkTime($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'wg_report_working_day') d ON d.`value` = o.accidentWorkingDay COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentWorkingDay";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarWeekDay($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'wg_report_week_day') d ON d.`value` = DAYOFWEEK(o.accidentDate)";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY DAYOFWEEK(o.accidentDate)";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarAccidentType($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_category') d ON d.`value` = o.accidentCategory COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentCategory";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarPlace($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_place') d ON d.`value` = o.accidentPlace COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentPlace";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarInjuryType($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_injury_type') d ON d.`value` = o.accidentInjuryType COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentInjuryType";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarBody($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_body_part') d ON d.`value` = o.accidentBodyPart COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentBodyPart";

		$query = $sql.$where->sql.$groupBy;
        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarAgent($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_agent') d ON d.`value` = o.accidentAgent COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentAgent";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarMechanism($customerId, $year)
    {
        $sql = "SELECT o.`id`,
			o.customer_id,
      d.item `name`,
      d.`item` abbreviation,
      YEAR(o.`accidentDate`) `year`,
      '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_accident_mechanism') d ON d.`value` = o.accidentMechanism COLLATE utf8_general_ci";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY o.accidentMechanism";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarInsecureAct($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       UPPER(d.`name`) `name`,
       UPPER(d.`name`) abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_customer_occupational_investigation_al_cause ciac ON ciac.customer_investigation_id = o.id
INNER JOIN wg_investigation_cause d ON d.id = ciac.insecureAct";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY ciac.insecureAct";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarInsecureCondition($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       UPPER(d.`name`) `name`,
       UPPER(d.`name`) abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_customer_occupational_investigation_al_cause ciac ON ciac.customer_investigation_id = o.id
INNER JOIN wg_investigation_cause d ON d.id = ciac.insecureCondition";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY ciac.insecureCondition";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarWorkFactor($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       UPPER(d.`name`) `name`,
       UPPER(d.`name`) abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_customer_occupational_investigation_al_cause ciac ON ciac.customer_investigation_id = o.id
INNER JOIN wg_investigation_cause d ON d.id = ciac.workFactor";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY ciac.workFactor";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    public function getDashboardBarPersonalFactor($customerId, $year)
    {
        $sql = "SELECT o.`id`,
	   o.customer_id,
       UPPER(d.`name`) `name`,
       UPPER(d.`name`) abbreviation,
       YEAR(o.`accidentDate`) `year`,
       '#FF5A5E' color
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 1 THEN 1 END) ENE
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 2 THEN 1 END) FEB
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 3 THEN 1 END) MAR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 4 THEN 1 END) ABR
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 5 THEN 1 END) MAY
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 6 THEN 1 END) JUN
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 7 THEN 1 END) JUL
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 8 THEN 1 END) AGO
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 9 THEN 1 END) SEP
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 10 THEN 1 END) OCT
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 11 THEN 1 END) NOV
			, SUM(CASE WHEN MONTH(o.`accidentDate`) = 12 THEN 1 END) DIC
FROM wg_customer_occupational_investigation_al o
INNER JOIN wg_customer_occupational_investigation_al_cause ciac ON ciac.customer_investigation_id = o.id
INNER JOIN wg_investigation_cause d ON d.id = ciac.personalFactor";

        $where = $this->getDashBoardWhere($customerId, $year);
		$groupBy = " GROUP BY ciac.personalFactor";

		$query = $sql.$where->sql.$groupBy;

        $results = DB::select($query, $where->filters);

        return $results;
    }

    private function getDashBoardWhere($customerId, $year)
    {
        $criteria = new \stdClass();
        $where = '';
        $filters = array();

        if ($customerId != '' && $customerId != '0') {
            $where .= " WHERE o.customer_id = :customer_id";
            $filters["customer_id"] = $customerId;
        }

        if ($year != 0) {
            $where .= empty($where) ? " WHERE YEAR(o.`accidentDate`) = :year" : " AND YEAR(o.`accidentDate`) = :year";
            $filters["year"] = $year;
        }

        $criteria->sql = $where;
        $criteria->filters = $filters;

        return $criteria;
    }

    //-------------------------------------------------------------------END REVIEW

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        return $where == "" ? "" : " WHERE " . $where;
    }
}
