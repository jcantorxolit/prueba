<?php

namespace Wgroup\CustomerSafetyInspectionListItemDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerSafetyInspectionListItemDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_safety_inspection_list_item_document';

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

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->type, "customer_safety_inspection_document_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
