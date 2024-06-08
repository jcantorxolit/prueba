<?php

namespace Wgroup\CustomerInternalCertificateProgramRequirement;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInternalCertificateProgramRequirement extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_certificate_program_requirement';

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

    public function getRequirement()
    {
        return $this->getParameterByValue($this->requirement, "certificate_program_requirement");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
