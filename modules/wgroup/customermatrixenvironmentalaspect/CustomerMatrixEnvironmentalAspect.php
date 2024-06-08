<?php

namespace Wgroup\CustomerMatrixEnvironmentalAspect;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use Wgroup\CustomerMatrixEnvironmentalAspectImpact\CustomerMatrixEnvironmentalAspectImpact;
use Wgroup\CustomerMatrixEnvironmentalAspectImpact\CustomerMatrixEnvironmentalAspectImpactDTO;

/**
 * Idea Model
 */
class CustomerMatrixEnvironmentalAspect extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix_environmental_aspect';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'matrix' => ['Wgroup\CustomerMatrix\CustomerMatrix', 'key' => 'customer_matrix_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function getImpacts()
    {
        return CustomerMatrixEnvironmentalAspectImpactDTO::parse(CustomerMatrixEnvironmentalAspectImpact::whereCustomerMatrixEnvironmentalAspectId($this->id)->get());
    }


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
