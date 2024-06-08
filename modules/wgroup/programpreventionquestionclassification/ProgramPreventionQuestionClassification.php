<?php

namespace Wgroup\ProgramPreventionQuestionClassification;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ProgramPreventionQuestionClassification extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_progam_prevention_question_classification';

    public $belongsTo = [

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

    public function  getSize()
    {
        return $this->getParameterByValue($this->customer_size, "wg_customer_size");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
