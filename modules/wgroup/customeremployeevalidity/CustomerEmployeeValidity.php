<?php

namespace Wgroup\CustomerEmployeeValidity;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerEmployeeValidity extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_employee_validity';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'grade' => ['Wgroup\CertificateGrade\CertificateGrade', 'key' => 'certificate_grade_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
