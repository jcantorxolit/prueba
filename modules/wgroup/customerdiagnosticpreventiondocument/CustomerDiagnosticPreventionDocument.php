<?php

namespace Wgroup\CustomerDiagnosticPreventionDocument;

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
class CustomerDiagnosticPreventionDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic_prevention_document';

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
        'question' => ['Wgroup\CustomerDiagnosticPreventionDocumentQuestion\CustomerDiagnosticPreventionDocumentQuestion', 'key' => 'program_prevention_document_id', 'otherKey' => 'id'],
    ];

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

    public function getQuestions()
    {
        $query = "SELECT
				program_prevention_question_id programPreventionQuestionId
			FROM
				wg_customer_diagnostic_prevention_document_question
			WHERE
				customer_diagnostic_prevention_document_id = :customer_diagnostic_prevention_document_id";

        $whereArray = array();

        $whereArray["customer_diagnostic_prevention_document_id"] = $this->id;

        $results = DB::select($query, $whereArray);

        return $results;
    }
}
