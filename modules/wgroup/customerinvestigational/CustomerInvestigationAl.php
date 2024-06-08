<?php

namespace Wgroup\CustomerInvestigationAl;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCost;
use Wgroup\CustomerInvestigationAlCause\CustomerInvestigationAlCause;
use Wgroup\CustomerInvestigationAlCause\CustomerInvestigationAlCauseDTO;
use Wgroup\InvestigationAlCause\InvestigationAlCause;
use Wgroup\InvestigationAlCause\InvestigationAlCauseDTO;
use Wgroup\InvestigationAlEconomicActivity\InvestigationAlEconomicActivity;
use Wgroup\Models\InfoDetail;

/**
 * Idea Model
 */
class CustomerInvestigationAl extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al';

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

        'employeeCustomer' => ['RainLab\User\Models\Country', 'key' => 'employee_country_id', 'otherKey' => 'id'],
        'employeeState' => ['RainLab\User\Models\State', 'key' => 'employee_state_id', 'otherKey' => 'id'],
        'employeeCity' => ['Wgroup\Models\Town', 'key' => 'employee_city_id', 'otherKey' => 'id'],
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

    public function  getNotifiedBy()
    {
        return $this->getParameterByValue($this->notifiedBy, "investigation_notified_by");
    }

    public function  getAccidentType()
    {
        return $this->getParameterByValue($this->accidentType, "investigation_accident_type");
    }

    public function  getDxResolution()
    {
        return $this->getParameterByValue($this->dxResolution, "investigation_dx_resolution");
    }

    public function  getHazardType()
    {
        return $this->getParameterByValue($this->hazardType, "investigation_hazard_type");
    }

    public function  getInterventionPlan()
    {
        return $this->getParameterByValue($this->interventionPlan, "investigation_intervention_plan");
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

    public function  getAccidentPlace()
    {
        return $this->getParameterByValue($this->accidentPlace, "investigation_accident_place");
    }

    public function getCustomerPrincipalRiskClass()
    {
        return $this->getParameterByValue($this->customerPrincipalRiskClass, "investigation_risk_class");
    }

    public function getCustomerBranchRiskClass()
    {
        return $this->getParameterByValue($this->customerBranchRiskClass, "investigation_risk_class");
    }

    public function  getAccidentIsDeathCause()
    {
        return $this->getParameterByValue($this->accidentIsDeathCause, "diagnostic_accident_status");
    }

    public function  getAccidentCategory()
    {
        return $this->getParameterByValue($this->accidentCategory, "investigation_accident_category");
    }

    public function  getEmployeeLinkType()
    {
        return $this->getParameterByValue($this->employeeLinkType, "investigation_employee_link_type");
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

    public function  getEmployeeGender()
    {
        return $this->getParameterByValue($this->employeeGender, "gender");
    }

    public function  getEmployeeAFP()
    {
        return $this->getParameterByValue($this->employeeAFP, "afp");
    }

    public function  getEmployeeARL()
    {
        return $this->getParameterByValue($this->employeeARL, "arl");
    }

    public function  getEmployeeEPS()
    {
        return $this->getParameterByValue($this->employeeEPS, "eps");
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

    public function getInsecureAct()
    {
        return CustomerInvestigationAlCauseDTO::parse(CustomerInvestigationAlCause::whereCustomerInvestigationId($this->id)->whereType('CIAI')->get());
    }

    public function getInsecureCondition()
    {
        return CustomerInvestigationAlCauseDTO::parse(CustomerInvestigationAlCause::whereCustomerInvestigationId($this->id)->whereType('CICI')->get());
    }

    public function getWorkFactor()
    {
        return CustomerInvestigationAlCauseDTO::parse(CustomerInvestigationAlCause::whereCustomerInvestigationId($this->id)->whereType('CBFT')->get());
    }

    public function getPersonalFactor()
    {
        return CustomerInvestigationAlCauseDTO::parse(CustomerInvestigationAlCause::whereCustomerInvestigationId($this->id)->whereType('CBFP')->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
