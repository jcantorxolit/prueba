<?php

namespace Wgroup\CustomerPeriodicRequirementContractorType;

use BackendAuth;
use Log;
use MyProject\Proxies\__CG__\stdClass;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;

/**
 * Idea Model
 */
class CustomerPeriodicRequirementContractorType extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_periodic_requirement_contractor_type';

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

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    public function  getPeriod()
    {
        $period = new \stdClass();

        $period->value = $this->period;
        $period->item = $this->period ? substr($this->period, 0, -2) . "-" . substr($this->period, -2) : "";

        return $period;
    }

    public function getCustomerContractorType()
    {
        return $this->getCustomerParametersBy($this->customer_contractor_type_id);
    }

    protected function getCustomerParametersBy($value)
    {
        return CustomerParameterDTO::parse(CustomerParameter::whereId($value)->first());
    }

    protected function getCustomerParametersByValue($value, $group, $ns = "wgroup")
    {
        return CustomerParameterDTO::parse(CustomerParameter::whereNamespace($ns)->whereGroup($group)->whereCustomerId($value)->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
