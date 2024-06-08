<?php

namespace Wgroup\CustomerImprovementPlanTracking;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigActivity\CustomerConfigActivity;
use Wgroup\Models\Customer;

/**
 * Idea Model
 */
class CustomerImprovementPlanTracking extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_tracking';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'improvementPlan' => ['Wgroup\CustomerImprovementPlan\CustomerImprovementPlan', 'key' => 'customer_improvement_plan_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];


    public function getResponsible()
    {
        return Customer::getAgentOrUser($this->responsible, $this->responsibleType);;
    }

    public function getStatus()
    {
        return $this->getParameterByValue($this->status, 'improvement_plan_tracking_status');;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
