<?php

namespace Wgroup\CustomerOccupationalReportIncidentFactor;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerOccupationalReportIncidentFactor extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_report_incident_factor';

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

    public function  getTypeLinkage()
    {
        return $this->getParameterByValue($this->type_linkage, "wg_type_linkage");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}