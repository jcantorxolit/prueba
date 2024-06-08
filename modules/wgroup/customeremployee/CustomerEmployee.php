<?php

namespace Wgroup\CustomerEmployee;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocument;
use DB;

/**
 * Town Model
 */
class CustomerEmployee extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_employee';

    /*
     * Validation
     */
    public $rules = [
        'customer_id' => 'required'
    ];

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'employee' => ['Wgroup\Employee\Employee', 'key' => 'employee_id', 'otherKey' => 'id'],
        'workPlaceModel' => ['Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlace', 'key' => 'workPlace', 'otherKey' => 'id'],
        'jobModel' => ['Wgroup\CustomerConfigJob\CustomerConfigJob', 'key' => 'job', 'otherKey' => 'id'],
        'occupationModel' => ['Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivity', 'key' => 'occupation', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'validityList' => ['Wgroup\CustomerEmployeeValidity\CustomerEmployeeValidity']
    ];

    public $timestamps = false;

    public function getType()
    {
        return $this->getParameterByValue($this->type, "bunit");
    }

    public function agents($customerId)
    {

        $query = (new Agent())->query();
        return $query
            ->join("wg_customer_employee", "wg_customer_agent.agent_id", "=", "wg_agent.id")
            ->where("wg_customer_agent.type", $this->type)
            ->where("wg_customer_agent.customer_id", $customerId)
            ->get(["wg_agent.*"]);
    }


    public function getContractType()
    {
        return $this->getParameterByValue($this->contractType, "employee_contract_type");
    }

    public function getOccupation()
    {
        return $this->getParameterByValue($this->occupation, "employee_occupation");
    }

    public function getJob()
    {
        return $this->getParameterByValue($this->job, "wg_employee_job");
    }

    public function getWorkShift()
    {
        return $this->getParameterByValue($this->work_shift, "work_shifts");
    }

    public function getCountAttachment()
    {
        return CustomerEmployeeDocument::where("customer_employee_id", $this->id)->count();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
