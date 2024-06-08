<?php

namespace Wgroup\ImprovementPlanCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;

/**
 * Idea Model
 */
class ImprovementPlanCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_improvement_plan_cause';

    /*
     * Validation
     */
    public $rules = [
    ];

    public function getCategory()
    {
        return ImprovementPlanCauseCategory::find($this->improvement_plan_cause_category_id);
    }
}
