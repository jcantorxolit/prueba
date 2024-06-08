<?php

namespace Wgroup\CustomerContractor;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;

/**
 * Idea Model
 */
class CustomerContractor extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_contractor';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'contractor' => ['Wgroup\Models\Customer', 'key' => 'contractor_id', 'otherKey' => 'id'],
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

    public function getType()
    {
        return $this->getCustomerParametersById($this->contractor_type_id);
    }

    protected function getCustomerParametersById($id)
    {
        return CustomerParameter::whereId($id)->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
