<?php

namespace Wgroup\CustomerMatrixEnvironmentalAspectImpact;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerMatrixEnvironmentalAspectImpactStaging extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix_environmental_aspect_impact_staging';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'aspect' => ['Wgroup\CustomerMatrixEnvironmentalAspect\CustomerMatrixEnvironmentalAspect', 'key' => 'customer_matrix_environmental_aspect_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
