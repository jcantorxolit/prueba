<?php

namespace Wgroup\CustomerInternalCertificateProgramSpeciality;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInternalCertificateProgramSpeciality extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_certificate_program_speciality';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'program' => ['Wgroup\CustomerInternalCertificateProgram\CustomerInternalCertificateProgram', 'key' => 'customer_internal_certificate_program_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];

    public function  getCategory()
    {
        return $this->getParameterByValue($this->category, "certificate_program_speciality_category");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
