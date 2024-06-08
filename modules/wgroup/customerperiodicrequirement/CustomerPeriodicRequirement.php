<?php

namespace Wgroup\CustomerPeriodicRequirement;

use BackendAuth;
use Log;
use MyProject\Proxies\__CG__\stdClass;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerPeriodicRequirementContractorType\CustomerPeriodicRequirementContractorType;
use Wgroup\CustomerPeriodicRequirementContractorType\CustomerPeriodicRequirementContractorTypeDTO;

/**
 * Idea Model
 */
class CustomerPeriodicRequirement extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_periodic_requirement';

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
        $period->item = $this->period ? substr($this->period,0, -2)."-".substr($this->period, -2) : "";

        return $period;
    }

    public function getContractorTypes()
    {
        return CustomerPeriodicRequirementContractorTypeDTO::parse(CustomerPeriodicRequirementContractorType::whereCustomerPeriodicRequirementId($this->id)->get());
        //return CustomerPeriodicRequirementContractorType::whereCustomerPeriodicRequirementId($this->id)->get();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
