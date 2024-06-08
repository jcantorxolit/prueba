<?php

namespace Wgroup\CustomerOccupationalReportAlWitness;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerOccupationalReportWitness extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_report_al_witness';

    public $belongsTo = [
        'report' => ['Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReport', 'key' => 'customer_occupational_report_al_id', 'otherKey' => 'id'],
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

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->document_type, "tipodoc");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
