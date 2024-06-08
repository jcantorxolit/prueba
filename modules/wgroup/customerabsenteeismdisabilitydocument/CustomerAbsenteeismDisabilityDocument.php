<?php

namespace Wgroup\CustomerAbsenteeismDisabilityDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerAbsenteeismDisabilityDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_absenteeism_disability_document';

    public $belongsTo = [
        'agent' => ['RainLab\User\Models\User', 'key' => 'agent_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
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

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->type, "absenteeism_disability_document_type");
    }

    public static function hasDocumentType($customerDisabilityId, $documentType)
    {
        return CustomerAbsenteeismDisabilityDocument::where('customer_disability_id', $customerDisabilityId)->where('type', $documentType)->count() > 0;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
