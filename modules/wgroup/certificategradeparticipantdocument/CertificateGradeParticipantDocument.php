<?php

namespace Wgroup\CertificateGradeParticipantDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CertificateGradeParticipantDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_grade_participant_document';

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
        $model = new CertificateGradeParticipantDocument();

        return $model->getParameterByValue($requirement, "certificate_program_requirement");
    }

    public function  getRequirement()
    {
        return $this->getParameterByValue($this->requirement, "certificate_program_requirement");
    }

    public static function hasDocumentType($customerDisabilityId, $documentType)
    {
        return CertificateGradeParticipantDocument::where('customer_disability_id', $customerDisabilityId)->where('type', $documentType)->count() > 0;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
