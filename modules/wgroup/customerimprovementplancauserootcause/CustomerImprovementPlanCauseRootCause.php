<?php

namespace Wgroup\CustomerImprovementPlanCauseRootCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerImprovementPlanCause\CustomerImprovementPlanCause;
use Wgroup\CustomerImprovementPlanCause\CustomerImprovementPlanCauseDTO;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategoryDTO;

/**
 * Idea Model
 */
class CustomerImprovementPlanCauseRootCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_cause_root_cause';

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

    public function getParent()
    {
        //return  CustomerImprovementPlanCauseDTO::parse(CustomerImprovementPlanCause::find($this->customer_improvement_plan_cause_id));
        $parent = CustomerImprovementPlanCause::find($this->customer_improvement_plan_cause_id);
        if ($parent != null) {
            $parent->cause = ImprovementPlanCauseCategoryDTO::parse(ImprovementPlanCauseCategory::find($parent->cause));
        }
        return  $parent;
    }

    public function getProbabilityOccurrence()
    {
        $this->getParameterByValue($this->probabilityOccurrence, 'improvement_plan_root_cause_probability_occur');
    }

    public function getEffect()
    {
        $this->getParameterByValue($this->effect, 'improvement_plan_root_cause_effect');
    }

    public function getDetectionLevel()
    {
        $this->getParameterByValue($this->detectionLevel, 'improvement_plan_root_detection_level');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
