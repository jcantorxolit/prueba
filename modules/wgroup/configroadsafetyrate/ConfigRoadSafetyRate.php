<?php

namespace Wgroup\ConfigRoadSafetyRate;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\ImprovementPlanCause\ImprovementPlanCause;
use Wgroup\ImprovementPlanCause\ImprovementPlanCauseDTO;

/**
 * Idea Model
 */
class ConfigRoadSafetyRate extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_config_road_safety_rate';

    public $belongsTo = [
    ];


    /*
     * Validation
     */
    public $rules = [
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
