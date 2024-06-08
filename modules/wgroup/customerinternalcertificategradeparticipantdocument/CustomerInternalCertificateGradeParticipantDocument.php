<?php

namespace Wgroup\CustomerInternalCertificateGradeParticipantDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInternalCertificateGradeParticipantDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_certificate_grade_participant_document';

    public $belongsTo = [
        'agent' => ['RainLab\User\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public static function findRequirement($requirement)
    {
        $model = new CustomerInternalCertificateGradeParticipantDocument();

        return $model->getParameterByValue($requirement, "customer_internal_certificate_program_requirement");
    }

    public function  getRequirement()
    {
        return $this->getParameterByValue($this->requirement, "customer_internal_certificate_program_requirement");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
