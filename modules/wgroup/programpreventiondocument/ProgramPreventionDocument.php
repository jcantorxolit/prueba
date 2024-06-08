<?php

namespace Wgroup\ProgramPreventionDocument;

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
class ProgramPreventionDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_progam_prevention_document';

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

    public $hasMany = [
        'question' => ['Wgroup\ProgramPreventionDocumentQuestion\ProgramPreventionDocumentQuestion', 'key' => 'program_prevention_document_id', 'otherKey' => 'id'],
    ];

    public static function findRequirement($requirement)
    {
        $model = new ProgramPreventionDocument();

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
        return ProgramPreventionDocument::where('customer_employee_id', $customerEmployeeId)->where('type', $documentType)->count() > 0;
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "program_prevention_document_classification");
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

    public function getQuestions()
    {
        $query = "SELECT
				program_prevention_question_id programPreventionQuestionId
			FROM
				wg_progam_prevention_document_question
			WHERE
				program_prevention_document_id = :program_prevention_document_id";

        $whereArray = array();

        $whereArray["program_prevention_document_id"] = $this->id;

        $results = DB::select($query, $whereArray);

        return $results;
    }
}
