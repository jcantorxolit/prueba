<?php

namespace Wgroup\ConfigMinimumStandardCycle;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\ImprovementPlanCause\ImprovementPlanCause;
use Wgroup\ImprovementPlanCause\ImprovementPlanCauseDTO;

/**
 * Idea Model
 */
class ConfigMinimumStandardCycle extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_config_minimum_standard_cycle';

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
