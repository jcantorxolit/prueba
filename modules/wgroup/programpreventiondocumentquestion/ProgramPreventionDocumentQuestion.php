<?php

namespace Wgroup\ProgramPreventionDocumentQuestion;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ProgramPreventionDocumentQuestion extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_progam_prevention_document_question';

    public $belongsTo = [
        'document' => ['Wgroup\ProgramPreventionDocument\ProgramPreventionDocument', 'key' => 'wg_progam_prevention_document_id', 'otherKey' => 'id'],
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
