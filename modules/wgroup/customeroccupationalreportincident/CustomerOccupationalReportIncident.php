<?php

namespace Wgroup\CustomerOccupationalReportIncident;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivity;
use Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivityDTO;
use Wgroup\CustomerOccupationalReportIncidentWitness\CustomerOccupationalReportIncidentWitness;

/**
 * Idea Model
 */
class CustomerOccupationalReportIncident extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_report_incident';

    public $belongsTo = [
        'employee' => ['Wgroup\CustomerEmployee\CustomerEmployee', 'key' => 'customer_employee_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'town' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],
        'customerState' => ['RainLab\User\Models\State', 'key' => 'customer_state_id', 'otherKey' => 'id'],
        'customerTown' => ['Wgroup\Models\Town', 'key' => 'customer_city_id', 'otherKey' => 'id'],
        'customerBranchState' => ['RainLab\User\Models\State', 'key' => 'customer_branch_state_id', 'otherKey' => 'id'],
        'customerBranchTown' => ['Wgroup\Models\Town', 'key' => 'customer_branch_city_id', 'otherKey' => 'id'],
        'accidentState' => ['RainLab\User\Models\State', 'key' => 'accident_state_id', 'otherKey' => 'id'],
        'accidentTown' => ['Wgroup\Models\Town', 'key' => 'accident_city_id', 'otherKey' => 'id'],
        'jobModel' => ['Wgroup\CustomerConfigJob\CustomerConfigJob', 'key' => 'job', 'otherKey' => 'id'],
        'occupationModel' => ['Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivity', 'key' => 'occupation', 'otherKey' => 'id'],
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

    public function  getTypeLinkage()
    {
        return $this->getParameterByValue($this->type_linkage, "wg_type_linkage");
    }

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->document_type, "employee_document_type");
    }

    public function  getCustomerDocumentType()
    {
        return $this->getParameterByValue($this->customer_document_type, "tipodoc");
    }

    public function  getResponsibleDocumentType()
    {
        return $this->getParameterByValue($this->report_responsible_document_type, "tipodoc");
    }

    public function  getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function  getOccupation()
    {
        return $this->getParameterByValue($this->occupation, "employee_occupation");
    }

    public function  getZone()
    {
        return $this->getParameterByValue($this->zone, "wg_report_zone");
    }

    public function  getCustomerZone()
    {
        return $this->getParameterByValue($this->customer_zone, "wg_report_zone");
    }

    public function  getCustomerBranchZone()
    {
        return $this->getParameterByValue($this->customer_branch_zone, "wg_report_zone");
    }

    public function  getAccidentZone()
    {
        return $this->getParameterByValue($this->accident_zone, "wg_report_zone");
    }

    public function  getWorkingDay()
    {
        return $this->getParameterByValue($this->working_day, "wg_report_regular_work");
    }

    public function  getEps()
    {
        return $this->getParameterByValue($this->eps, "eps");
    }

    public function  getArl()
    {
        return $this->getParameterByValue($this->arl, "arl");
    }

    public function  getIsAfp()
    {
        return $this->getConditionalList($this->is_afp);
    }

    public function  getAfp()
    {
        return $this->getParameterByValue($this->afp, "afp");
    }

    public function  getIsCustomerBranchName()
    {
        return $this->getConditionalList($this->is_customer_branch_same);
    }

    public function  getCustomerEmploymentRelationship()
    {
        return $this->getParameterByValue($this->customer_type_employment_relationship, "wg_report_employment_relationship");
    }

    public function  getCustomerEconomicActivity()
    {
        return $this->getParameterByValue($this->customer_economic_activity, "wg_economic_activity");
    }

    public function  getCustomerBranchEconomicActivity()
    {
        return $this->getParameterByValue($this->customer_branch_economic__activity, "wg_economic_activity");
    }

    public function  getAccidentWeekDay()
    {
        return $this->getParameterByValue($this->accident_week_day, "wg_report_week_day");
    }

    public function  getAccidentWorkingDay()
    {
        return $this->getParameterByValue($this->accident_working_day, "wg_report_working_day");
    }

    public function  getAccidentRegularWork()
    {
        return $this->getConditionalList($this->accident_regular_work);
    }

    public function  getAccidentRegularWorkText()
    {
        $data = CustomerConfigJobActivityDTO::parse(CustomerConfigJobActivity::find($this->accident_regular_work_text));

        return $data && $data->activity ? $data->activity : null;
    }

    public function  getAccidentType()
    {
        return $this->getParameterByValue($this->accident_type, "wg_report_accident_type");
    }

    public function  getAccidentDeathCause()
    {
        return $this->getConditionalList($this->accident_death_cause);
    }

    public function  getAccidentLocation()
    {
        return $this->getParameterByValue($this->accident_location, "wg_report_location");
    }

    public function  getAccidentPlace()
    {
        return $this->getParameterByValue($this->accident_place, "wg_report_place");
    }

    public function  getIsAccidentWitness()
    {
        return $this->getConditionalList($this->is_accident_witness);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    private function getConditionalList($value)
    {
        if ($value === null || $value === "") {
            return null;
        }

        $condition = new \stdClass();
        $condition->item = $value == 1 ? "Si" : "No";
        $condition->value = $value;

        return $condition;
    }

    public function  getLesions()
    {
        $query = "SELECT
		IFNULL(coral.id, 0) id
	, IFNULL(coral.customer_occupational_report_incident_id, 0) customerOccupationalReportAlId
	, lty.value itemId
	, lty.item description
	, case when coral.lesion_id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_lesion_type' ) lty
LEFT JOIN
  ( SELECT coral.lesion_id, coral.id, coral.customer_occupational_report_incident_id
   FROM wg_customer_occupational_report_incident_lesion coral
   INNER JOIN wg_customer_occupational_report_incident cora ON coral.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) coral ON lty. VALUE = coral.lesion_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getBodies()
    {
        $query = "SELECT
		IFNULL(corab.id, 0) id
	, IFNULL(corab.customer_occupational_report_incident_id, 0) customerOccupationalReportAlId
	, lty.value itemId
	, lty.item description
	, case when corab.body_part_id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_body_part' ) lty
LEFT JOIN
  ( SELECT corab.body_part_id, corab.id, corab.customer_occupational_report_incident_id
   FROM wg_customer_occupational_report_incident_body corab
   INNER JOIN wg_customer_occupational_report_incident cora ON corab.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) corab ON lty. VALUE = corab.body_part_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getFactors()
    {
        $query = "SELECT
		IFNULL(coraf.id, 0) id
	, IFNULL(coraf.customer_occupational_report_incident_id, 0) customerOccupationalReportAlId
	, lty.value itemId
	, lty.item description
	, case when coraf.factor_id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_factor' ) lty
LEFT JOIN
  ( SELECT coraf.factor_id, coraf.id, coraf.customer_occupational_report_incident_id
   FROM wg_customer_occupational_report_incident_factor coraf
   INNER JOIN wg_customer_occupational_report_incident cora ON coraf.customer_occupational_report_incident_id = cora.id
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
	, IFNULL(coram.customer_occupational_report_incident_id, 0) customerOccupationalReportAlId
	, lty.value itemId
	, lty.item description
	, case when coram.mechanism_id is not null then true else false end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_mechanism' ) lty
LEFT JOIN
  ( SELECT coram.mechanism_id, coram.id, coram.customer_occupational_report_incident_id
   FROM wg_customer_occupational_report_incident_mechanism coram
   INNER JOIN wg_customer_occupational_report_incident cora ON coram.customer_occupational_report_incident_id = cora.id
   WHERE cora.id = :id) coram ON lty. VALUE = coram.mechanism_id COLLATE utf8_general_ci";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));

        return $results;
    }

    public function  getWitnesses()
    {
        return CustomerOccupationalReportIncidentWitness::whereCustomerOccupationalReportIncidentId($this->id)->get();
    }
}
