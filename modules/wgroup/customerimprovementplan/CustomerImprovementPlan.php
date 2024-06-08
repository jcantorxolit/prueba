<?php

namespace Wgroup\CustomerImprovementPlan;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerImprovementPlanAlert\CustomerImprovementPlanAlert;
use Wgroup\CustomerImprovementPlanAlert\CustomerImprovementPlanAlertDTO;
use Wgroup\CustomerImprovementPlanTracking\CustomerImprovementPlanTracking;
use Wgroup\CustomerImprovementPlanTracking\CustomerImprovementPlanTrackingDTO;
use Wgroup\Models\Customer;

/**
 * Idea Model
 */
class CustomerImprovementPlan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan';

    public $belongsTo = [

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

    public function  getEntity()
    {
        return $this->getParameterByValue($this->entityName, "improvement_plan_origin");
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "improvement_plan_type");
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "improvement_plan_status");
    }

    public function  getResponsible()
    {
        return Customer::getAgentOrUser($this->responsible, $this->responsibleType);
    }

    public function getAlertList()
    {
        return CustomerImprovementPlanAlertDTO::parse(CustomerImprovementPlanAlert::whereCustomerImprovementPlanId($this->id)->get());
    }

    public function getTrackingList()
    {
        return CustomerImprovementPlanTrackingDTO::parse(CustomerImprovementPlanTracking::whereCustomerImprovementPlanId($this->id)->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected static function getParameter($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getEntityOrigin($entityCode)
    {
        return CustomerImprovementPlan::getParameter($entityCode, "improvement_plan_origin");
    }
}
