<?php

namespace Wgroup\CertificateProgramRequirement;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CertificateProgramRequirement extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_program_requirement';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'program' => ['Wgroup\CertificateProgram\CertificateProgram', 'key' => 'certificate_program_id', 'otherKey' => 'id'],
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
