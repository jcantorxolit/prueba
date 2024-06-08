<?php

namespace Wgroup\CustomerMatrixDataControl;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigActivity\CustomerConfigActivity;

/**
 * Idea Model
 */
class CustomerMatrixDataControl extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix_data_control';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'matrix' => ['Wgroup\CustomerMatrixData\CustomerMatrixData', 'key' => 'customer_matrix_data_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];


    public function getType()
    {
        return $this->getParameterByValue($this->type, 'matrix_control_type');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
