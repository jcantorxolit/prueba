<?php

namespace Wgroup\CustomerContractDetailDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerContractDetailDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_contract_detail_document';

    public $belongsTo = [

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
        return $this->getParameterByValue($this->type, "contract_detail_document_type");
    }

    public static function hasDocumentType($contractDetailId, $documentType)
    {
        return CustomerContractDetailDocument::where('contract_detail_id', $contractDetailId)->where('type', $documentType)->count() > 0;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
