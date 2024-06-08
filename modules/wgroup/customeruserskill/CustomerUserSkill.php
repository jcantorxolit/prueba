<?php

namespace Wgroup\CustomerUserSkill;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;

/**
 * Idea Model
 */
class CustomerUserSkill extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_user_skill';

    public $belongsTo = [
        'user' => ['Wgroup\CustomerUser\CustomerUser', 'key' => 'user_id', 'otherKey' => 'id'],
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

    public function  getSkill()
    {
        return $this->getCustomerParametersByValue($this->skill);
    }

    public function  getGender(){
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function  getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected  function getCustomerParametersByValue($value){
        return  CustomerParameterDTO::parse(CustomerParameter::whereId($value)->first());
    }
}
