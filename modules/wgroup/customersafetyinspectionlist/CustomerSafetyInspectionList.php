<?php

namespace Wgroup\CustomerSafetyInspectionList;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerSafetyInspectionList extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_safety_inspection_list';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'configList' => ['Wgroup\CustomerSafetyInspectionConfigList\CustomerSafetyInspectionConfigList', 'key' => 'customer_safety_inspection_config_list_id', 'otherKey' => 'id'],
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

    public function  getDataType()
    {
        return $this->getParameterByValue($this->dataType, "wg_data_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
