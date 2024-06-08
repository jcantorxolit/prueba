<?php

namespace Wgroup\CustomerAbsenteeismDisabilityReportAL;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class CustomerAbsenteeismDisabilityReportAL extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_absenteeism_disability_report_al';

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


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
