<?php

namespace Wgroup\CustomerConfigJobActivityDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfig\ConfigGeneral;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityIntervention;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionDTO;
use DB;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;

/**
 * Idea Model
 */
class CustomerConfigJobActivityDocument extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_job_activity_document';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'activity' => ['Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivity', 'key' => 'job_activity_id', 'otherKey' => 'id'],
        'classificationModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardClassification', 'key' => 'classification', 'otherKey' => 'id'],
        'descriptionModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardDescription', 'key' => 'description', 'otherKey' => 'id'],
        'typeModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardType', 'key' => 'type', 'otherKey' => 'id'],
        'effectModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardEffect', 'key' => 'health_effect', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    protected  function getInterventions(){
        return  CustomerConfigJobActivityIntervention::whereJobActivityHazardId($this->id)->get();
    }

    public function deleteInterventions()
    {
        CustomerConfigJobActivityIntervention::whereJobActivityHazardId($this->id)->delete();
    }

    public function getRequirement()
    {
        $requirementModel = null;

        $requirementModel = $this->getEmployeeDocumentType($this->type);

        if ($requirementModel == null) {
            $requirementModel =  $this->getCustomerParameterById($this->type, "employeeDocumentType");
        }

        return $requirementModel;
    }

    protected  function getConfigByValue($value, $type = ""){
        return  ConfigGeneral::whereId($value)->first();
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
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
