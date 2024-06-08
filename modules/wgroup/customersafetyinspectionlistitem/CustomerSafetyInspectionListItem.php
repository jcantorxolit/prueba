<?php

namespace Wgroup\CustomerSafetyInspectionListItem;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerSafetyInspectionListItem extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_safety_inspection_list_item';

    public $belongsTo = [
        'group' => ['Wgroup\CustomerSafetyInspectionConfigListGroup\CustomerSafetyInspectionConfigListGroup', 'key' => 'customer_safety_inspection_config_list_group_id', 'otherKey' => 'id'],
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

    public static function  getAction($value)
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("wg_safety_inspection_action")->whereValue($value)->first();
    }

    public function  getDataType()
    {
        return $this->getParameterByValue($this->dataType, "wg_data_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
