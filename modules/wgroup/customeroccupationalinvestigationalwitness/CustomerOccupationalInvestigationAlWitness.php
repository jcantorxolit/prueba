<?php

namespace Wgroup\CustomerOccupationalInvestigationAlWitness;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerOccupationalInvestigationAlWitness extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_investigation_al_witness';

    public $belongsTo = [
        'report' => ['Wgroup\CustomerOccupationalReportIncident\CustomerOccupationalReportIncident', 'key' => 'customer_occupational_report_incident_id', 'otherKey' => 'id'],
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

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "investigation_testimony_type");
    }

    public function  getIsWatching()
    {
        return $this->getParameterByValue($this->isWatching, "diagnostic_accident_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
