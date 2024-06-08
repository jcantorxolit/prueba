<?php

namespace Wgroup\CertificateProgram;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CertificateProgram extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_program';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [

    ];

    public $hasMany = [
        'specialities' => ['Wgroup\CertificateProgramSpeciality\CertificateProgramSpeciality'],
        'requirements' => ['Wgroup\CertificateProgramRequirement\CertificateProgramRequirement'],
    ];

    public function  getStatus()
    {
        return $this->isActive == 1 ? true : false;
    }

    public function  getCurrency()
    {
        return $this->getParameterByValue($this->currency, "certificate_program_currency");
    }

    public function  getCategory()
    {
        return $this->getParameterByValue($this->category, "certificate_program_category");
    }

    public function  getSpeciality()
    {
        return $this->getParameterByValue($this->speciality, "agent_skill");
    }

    public function  getValidityType()
    {
        return $this->getParameterByValue($this->validityType, "certificate_program_validity_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
