<?php

namespace Wgroup\CustomerSafetyInspectionConfigList;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerSafetyInspectionConfigListValidation\CustomerSafetyInspectionConfigListValidation;
use Wgroup\CustomerSafetyInspectionConfigListValidation\CustomerSafetyInspectionConfigListValidationDTO;

/**
 * Idea Model
 */
class CustomerSafetyInspectionConfigList extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_safety_inspection_config_list';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'group' => ['Wgroup\CustomerSafetyInspectionConfigListGroup\CustomerSafetyInspectionConfigListGroup']
    ];

    public function  getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    public function getValidationByType($type)
    {
        return CustomerSafetyInspectionConfigListValidationDTO::parse(CustomerSafetyInspectionConfigListValidation::whereCustomerSafetyInspectionConfigListId($this->id)->whereType($type)->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
