<?php

namespace AdeN\Api\Modules\Customer\Employee\Staging;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEmployeeStagingModel extends Model
{

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_employee_staging";

    public $belongsTo = [
        'workPlaceModel' => ['Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlace', 'key' => 'workPlace', 'otherKey' => 'id'],
        'jobModel' => ['Wgroup\CustomerConfigJob\CustomerConfigJob', 'key' => 'job', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'name'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'name'],
        'city' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'name'],
    ];

    public function  getProfession()
    {
        return $this->getParameterByValue($this->profession, "employee_profession");
    }

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function  getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function  getAFP()
    {
        return $this->getParameterByValue($this->afp, "afp");
    }

    public function  getARL()
    {
        return $this->getParameterByValue($this->arl, "arl");
    }

    public function getContractType()
    {
        return $this->getParameterByValue($this->contractType, "employee_contract_type");
    }

    public function  getEPS()
    {
        return $this->getParameterByValue($this->eps, "eps");
    }

    public function getRh()
    {
        return $this->getParameterByValue($this->rh, "wg_employee_type_rh");
    }

    public function  getWorkShift()
    {
        return $this->getParameterByValue($this->work_shift, "work_shifts");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
