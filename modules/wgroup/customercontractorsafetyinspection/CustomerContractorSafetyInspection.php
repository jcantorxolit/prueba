<?php

namespace Wgroup\CustomerContractorSafetyInspection;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;


/**
 * Idea Model
 */
class CustomerContractorSafetyInspection extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_contractor_safety_inspection';

    public $belongsTo = [
        'header' => ['Wgroup\CustomerContractorSafetyInspectionConfigHeader\CustomerContractorSafetyInspectionConfigHeader', 'key' => 'customer_safety_inspection_header_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'lists' => ['Wgroup\CustomerContractorSafetyInspectionList\CustomerContractorSafetyInspectionList']
    ];

    public function  getAgent()
    {
        $results = DB::table('wg_agent')
            ->where('wg_agent.id', $this->agentId)
            ->first();

        return $results;
    }

    public function  getContractorType()
    {
        return $this->getCustomerParametersByValue($this->contractorType, "contractorTypes");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
    protected function getCustomerParametersByValue($value, $group, $ns = "wgroup")
    {
        return CustomerParameter::whereNamespace($ns)->whereGroup($group)->whereId($value)->first();
    }
}
