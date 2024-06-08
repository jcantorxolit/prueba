<?php

namespace Wgroup\CustomerEmployeeCriticalActivity;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;

/**
 * Idea Model
 */
class CustomerEmployeeCriticalActivity extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_employee_critical_activity';

    public $belongsTo = [

    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public static function findRequirement($requirement)
    {
        $model = new CustomerEmployeeCriticalActivity();

        $requirementModel = null;

        $requirementModel = $model->getParameterByValue($requirement, "wg_employee_attachment");

        if ($requirementModel == null) {
            $requirementModel =  $model->getCustomerParameterById($requirement, "employeeDocumentType");
        }

        return $requirementModel;//$model->getParameterByValue($requirement, "customer_employee_requirement");
    }

    public function getRequirement()
    {
        $requirementModel = null;

        $requirementModel = $this->getEmployeeDocumentType($this->requirement);

        if ($requirementModel == null) {
            $requirementModel =  $this->getCustomerParameterById($this->requirement, "employeeDocumentType");
        }

        return $requirementModel;

        //return $this->getParameterByValue($this->requirement, "customer_employee_requirement");
    }

    public static function hasDocumentType($customerEmployeeId, $documentType)
    {
        return CustomerEmployeeCriticalActivity::where('customer_employee_id', $customerEmployeeId)->where('type', $documentType)->count() > 0;
    }

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "customer_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected  function getCustomerParameterById($value, $group, $ns = "wgroup"){
        return  CustomerParameterDTO::parse(CustomerParameter::whereNamespace($ns)->whereGroup($group)->whereId($value)->first());
    }

    public function getEmployeeDocumentType($value)
    {
        $query = "
select
  `value` id, '' customerId, namespace, `group`, item `value`, '' `data`, 1 isActive
from
  system_parameters
where namespace = 'wgroup' and `group` = 'wg_employee_attachment' and `value` = :value";

        $whereArray = array();

        $whereArray["value"] = $value;

        $results = DB::select($query, $whereArray);

        return count($results) > 0 ? $results[0] : null;

        //return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }
}
