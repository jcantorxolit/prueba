<?php

namespace Wgroup\CustomerImprovementPlanActionPlan;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Budget\Budget;
use Wgroup\Budget\BudgetDTO;
use Wgroup\CustomerImprovementPlan\CustomerImprovementPlanDTO;
use Wgroup\CustomerImprovementPlanActionPlanNotified\CustomerImprovementPlanActionPlanNotified;
use Wgroup\CustomerImprovementPlanActionPlanNotified\CustomerImprovementPlanActionPlanNotifiedDTO;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCause;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCauseDTO;
use Wgroup\Models\Customer;
use DB;

/**
 * Idea Model
 */
class CustomerImprovementPlanActionPlan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_action_plan';

    public $belongsTo = [
        'improvementPlan' => ['Wgroup\CustomerImprovementPlan\CustomerImprovementPlan', 'key' => 'customer_improvement_plan_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
    ];

    public $hasMany = [

    ];

    public function getCustomer()
    {
        if ($this->improvementPlan != null) {
            return Customer::find($this->improvementPlan->customer_id);
        }

        return null;
    }

    public function getImprovementPlan()
    {
        return CustomerImprovementPlanDTO::parse($this->improvementPlan);
    }

    public function getEntry()
    {
        return BudgetDTO::parse(Budget::find($this->entry));
    }

    public function getRootCause()
    {
        return CustomerImprovementPlanCauseRootCauseDTO::parse(CustomerImprovementPlanCauseRootCause::find($this->customer_improvement_plan_cause_root_cause_id));
    }

    public function getResponsible()
    {
        return Customer::getAgentOrUser($this->responsible, $this->responsibleType);
    }

    public function getNotifiedList()
    {
        return CustomerImprovementPlanActionPlanNotifiedDTO::parse(CustomerImprovementPlanActionPlanNotified::whereCustomerImprovementPlanActionPlanId($this->id)->get());
    }

    public function  getStatus()
    {
        //TODO
        return $this->getParameterByValue($this->status, 'improvement_plan_status');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
