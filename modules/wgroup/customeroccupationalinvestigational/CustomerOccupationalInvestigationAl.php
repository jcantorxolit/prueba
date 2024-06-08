<?php

namespace Wgroup\CustomerOccupationalInvestigationAl;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCost;
use Wgroup\CustomerOccupationalInvestigationAlResponsible\CustomerOccupationalInvestigationAlResponsible;
use Wgroup\CustomerOccupationalInvestigationAlResponsible\CustomerOccupationalInvestigationAlResponsibleDTO;
use Wgroup\CustomerOccupationalInvestigationAlWitness\CustomerOccupationalInvestigationAlWitness;
use Wgroup\CustomerOccupationalInvestigationAlWitness\CustomerOccupationalInvestigationAlWitnessDTO;
use Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReport;
use Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReportDTO;
use Wgroup\InvestigationAlEconomicActivity\InvestigationAlEconomicActivity;
use Wgroup\Models\InfoDetail;
use DB;

/**
 * Idea Model
 */
class CustomerOccupationalInvestigationAl extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_investigation_al';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'employee' => ['Wgroup\CustomerEmployee\CustomerEmployee', 'key' => 'customer_employee_id', 'otherKey' => 'id'],
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
        'director' => ['Wgroup\Models\Agent', 'key' => 'director_id', 'otherKey' => 'id'],
        'investigator' => ['Wgroup\Models\Agent', 'key' => 'investigator_id', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'city' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],

        'accidentCountry' => ['RainLab\User\Models\Country', 'key' => 'accident_country_id', 'otherKey' => 'id'],
        'accidentState' => ['RainLab\User\Models\State', 'key' => 'accident_state_id', 'otherKey' => 'id'],
        'accidentCity' => ['Wgroup\Models\Town', 'key' => 'accident_city_id', 'otherKey' => 'id'],

        'customerBranchCountry' => ['RainLab\User\Models\Country', 'key' => 'customer_branch_country_id', 'otherKey' => 'id'],
        'customerBranchState' => ['RainLab\User\Models\State', 'key' => 'customer_branch_state_id', 'otherKey' => 'id'],
        'customerBranchCity' => ['Wgroup\Models\Town', 'key' => 'customer_branch_city_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getAccidentType()
    {
        return $this->getParameterByValue($this->accidentType, "wg_report_accident_type");
    }

    public function  getAccidentInjuryType()
    {
        return $this->getParameterByValue($this->accidentInjuryType, "investigation_accident_injury_type");
    }

    public function  getAccidentBodyPart()
    {
        return $this->getParameterByValue($this->accidentBodyPart, "investigation_accident_body_part");
    }

    public function  getAccidentMechanism()
    {
        return $this->getParameterByValue($this->accidentMechanism, "investigation_accident_mechanism");
    }

    public function  getAccidentAgent()
    {
        return $this->getParameterByValue($this->accidentAgent, "investigation_accident_agent");
    }

    public function  getAccidentWorkingDay()
    {
        return $this->getParameterByValue($this->accidentWorkingDay, "wg_report_working_day");
    }

    public function  getAccidentIsRegularWork()
    {
        return $this->getParameterByValue($this->accidentIsRegularWork, "diagnostic_accident_status");
    }

    public function  getAccidentZone()
    {
        return $this->getParameterByValue($this->accidentZone, "wg_report_zone");
    }

    public function  getAccidentLocation()
    {
        return $this->getParameterByValue($this->accidentLocation, "wg_report_location");
    }

    public function  getAccidentPlace()
    {
        return $this->getParameterByValue($this->accidentPlace, "wg_report_place");
    }

    public function  getAccidentIsDeathCause()
    {
        return $this->getParameterByValue($this->accidentIsDeathCause, "diagnostic_accident_status");
    }

    public function  getAccidentCategory()
    {
        return $this->getParameterByValue($this->accidentCategory, "wg_report_accident_type");
    }

    public function  getEmployeeLinkType()
    {
        return $this->getParameterByValue($this->employeeLinkType, "wg_type_linkage");
    }

    public function  getEmployeeZone()
    {
        return $this->getParameterByValue($this->employeeZone, "wg_report_zone");
    }

    public function  getEmployeeMissionWorkingDay()
    {
        return $this->getParameterByValue($this->employeeMissionWorkingDay, "investigation_employee_mission_working_day");
    }

    public function  getCustomerBranchZone()
    {
        return $this->getParameterByValue($this->customerBranchZone, "wg_report_zone");
    }

    public function  getCustomerPrincipalZone()
    {
        return $this->getParameterByValue($this->customerPrincipalZone, "wg_report_zone");
    }

    public function getInfoDetail()
    {
        return InfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
    }

    public function getCustomerPrincipalEconomicActivity()
    {
        return InvestigationAlEconomicActivity::find($this->customerPrincipalEconomicActivity);
    }

    public function getCustomerBranchEconomicActivity()
    {
        return InvestigationAlEconomicActivity::find($this->customerBranchEconomicActivity);
    }

    public function getReportAt()
    {
        return CustomerOccupationalReportDTO::parse(CustomerOccupationalReport::find($this->reportAt_id)) ;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public function  getBodies()
    {
        $query = "SELECT
		IFNULL(corab.id, 0) id
	, IFNULL(corab.customer_occupational_investigation_id, 0) customerOccupationalInvestigationId
	, lty.value itemId
	, lty.item description
	, case when corab.body_part_id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_body_part' ) lty
LEFT JOIN
  ( SELECT corab.body_part_id, corab.id, corab.customer_occupational_investigation_id
   FROM wg_customer_occupational_investigation_al_body corab
   INNER JOIN wg_customer_occupational_investigation_al cora ON corab.customer_occupational_investigation_id = cora.id
   WHERE cora.id = :id) corab ON lty. VALUE = corab.body_part_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getLesions()
    {
        $query = "SELECT
		IFNULL(coral.id, 0) id
	, IFNULL(coral.customer_occupational_investigation_id, 0) customerOccupationalInvestigationId
	, lty.value itemId
	, lty.item description
	, case when coral.lesion_id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_lesion_type' ) lty
LEFT JOIN
  ( SELECT coral.lesion_id, coral.id, coral.customer_occupational_investigation_id
   FROM wg_customer_occupational_investigation_al_lesion coral
   INNER JOIN wg_customer_occupational_investigation_al cora ON coral.customer_occupational_investigation_id = cora.id
   WHERE cora.id = :id) coral ON lty. VALUE = coral.lesion_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getFactors()
    {
        $query = "SELECT
		IFNULL(coraf.id, 0) id
	, IFNULL(coraf.customer_occupational_investigation_id, 0) customerOccupationalInvestigationId
	, lty.value itemId
	, lty.item description
	, case when coraf.factor_id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_factor' ) lty
LEFT JOIN
  ( SELECT coraf.factor_id, coraf.id, coraf.customer_occupational_investigation_id
   FROM wg_customer_occupational_investigation_al_factor coraf
   INNER JOIN wg_customer_occupational_investigation_al cora ON coraf.customer_occupational_investigation_id = cora.id
   WHERE cora.id = :id) coraf ON lty. VALUE = coraf.factor_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getMechanisms()
    {
        $query = "SELECT
		IFNULL(coram.id, 0) id
	, IFNULL(coram.customer_occupational_investigation_id, 0) customerOccupationalInvestigationId
	, lty.value itemId
	, lty.item description
	, case when coram.mechanism_id is not null then true else false end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_mechanism' ) lty
LEFT JOIN
  ( SELECT coram.mechanism_id, coram.id, coram.customer_occupational_investigation_id
   FROM wg_customer_occupational_investigation_al_mechanism coram
   INNER JOIN wg_customer_occupational_investigation_al cora ON coram.customer_occupational_investigation_id = cora.id
   WHERE cora.id = :id) coram ON lty. VALUE = coram.mechanism_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getWitnesses()
    {
        return CustomerOccupationalInvestigationAlWitnessDTO::parse(CustomerOccupationalInvestigationAlWitness::whereCustomerOccupationalInvestigationId($this->id)->get());
    }
}
