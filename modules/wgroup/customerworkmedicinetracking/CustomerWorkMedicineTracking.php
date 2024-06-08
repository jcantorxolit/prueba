<?php

namespace Wgroup\CustomerWorkMedicineTracking;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerWorkMedicineTracking extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_work_medicine_tracking';

    public $belongsTo = [
        'medicine' => ['Wgroup\WorkMedicine\WorkMedicine', 'key' => 'customer_work_medicine_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "work_medicine_tracking_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
