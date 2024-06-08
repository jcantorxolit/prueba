<?php

namespace Wgroup\CustomerDiagnosticPreventionDocumentQuestion;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnosticPreventionDocumentQuestion extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic_prevention_document_question';

    public $belongsTo = [
        'document' => ['Wgroup\CustomerDiagnosticPreventionDocument\CustomerDiagnosticPreventionDocument', 'key' => 'customer_diagnostic_prevention_document_id', 'otherKey' => 'id'],
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

    public function  getDataType()
    {
        return $this->getParameterByValue($this->dataType, "wg_data_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
