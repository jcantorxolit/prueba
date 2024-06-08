<?php

namespace Wgroup\CustomerInternalCertificateGradeCalendar;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInternalCertificateGradeCalendar extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_certificate_grade_calendar';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'grade' => ['Wgroup\CustomerInternalCertificateGrade\CustomerInternalCertificateGrade', 'key' => 'customer_internal_certificate_grade_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}