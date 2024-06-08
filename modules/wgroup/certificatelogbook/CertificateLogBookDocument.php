<?php

namespace Wgroup\CertificateLogBookDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CertificateLogBookDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_certificate_grade_participant_logbook';

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

    public static function hasDocumentType($customerDisabilityId, $documentType)
    {
        return CertificateLogBookDocument::where('customer_disability_id', $customerDisabilityId)->where('type', $documentType)->count() > 0;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
