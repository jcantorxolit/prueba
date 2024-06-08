<?php

namespace Wgroup\CustomerImprovementPlanCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCause;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCauseDTO;
use Wgroup\CustomerImprovementPlanCauseSubCause\CustomerImprovementPlanCauseSubCause;
use Wgroup\CustomerImprovementPlanCauseSubCause\CustomerImprovementPlanCauseSubCauseDTO;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategoryDTO;

/**
 * Idea Model
 */
class CustomerImprovementPlanCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_cause';

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

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function getCause()
    {
        return  ImprovementPlanCauseCategoryDTO::parse(ImprovementPlanCauseCategory::find($this->cause));
    }

    public function getSubCauses()
    {
        return CustomerImprovementPlanCauseSubCauseDTO::parse(CustomerImprovementPlanCauseSubCause::whereCustomerImprovementPlanCauseId($this->id)->get());
    }

    public function getRootCauses()
    {
        return CustomerImprovementPlanCauseRootCauseDTO::parse(CustomerImprovementPlanCauseRootCause::whereCustomerImprovementPlanCauseId($this->id)->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
