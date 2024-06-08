<?php

namespace Wgroup\ImprovementPlanCauseCategory;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Idea Model
 */
class ImprovementPlanCauseCategory extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_improvement_plan_cause_category';

    /*
     * Validation
     */
    public $rules = [
    ];

    public function items()
    {
        return $this->hasMany('Wgroup\ImprovementPlanCause\ImprovementPlanCause', 'improvement_plan_cause_category_id');
    }
}