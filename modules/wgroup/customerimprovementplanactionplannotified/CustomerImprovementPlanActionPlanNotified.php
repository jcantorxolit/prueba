<?php

namespace Wgroup\CustomerImprovementPlanActionPlanNotified;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\Customer;

/**
 * Idea Model
 */
class CustomerImprovementPlanActionPlanNotified extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_action_plan_notified';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'improvementPlanActionPlan' => ['Wgroup\wg_customer_improvement_plan_action_plan\wg_customer_improvement_plan_action_plan', 'key' => 'customer_improvement_plan_action_plan_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];


    public function getResponsible()
    {
        return Customer::getAgentOrUser($this->responsible, $this->responsibleType);;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
