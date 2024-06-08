<?php

namespace Wgroup\CustomerImprovementPlanActionPlanTask;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerImprovementPlanActionPlanNotified\CustomerImprovementPlanActionPlanNotified;
use Wgroup\CustomerImprovementPlanActionPlanNotified\CustomerImprovementPlanActionPlanNotifiedDTO;
use Wgroup\Models\Customer;

/**
 * Idea Model
 */
class CustomerImprovementPlanActionPlanTask extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_action_plan_task';

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
        return Customer::getAgentOrUser($this->responsible, $this->responsibleType);
    }

    public function getType()
    {
        return $this->getParameterByValue($this->type, 'improvement_plan_action_plan_task_type');
    }

    public function getStatus()
    {
        return $this->getParameterByValue($this->status, 'improvement_plan_action_plan_task_status');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
