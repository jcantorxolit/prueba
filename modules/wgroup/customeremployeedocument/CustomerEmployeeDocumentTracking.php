<?php

namespace Wgroup\CustomerEmployeeDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerEmployeeDocumentTracking extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_employee_document_tracking';

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

    public static function findRequirement($requirement)
    {
        $model = new CustomerEmployeeDocument();

        return $model->getParameterByValue($requirement, "customer_employee_requirement");
    }

    public function getRequirement()
    {
        return $this->getParameterByValue($this->requirement, "customer_employee_requirement");
    }

    public static function hasDocumentType($customerEmployeeId, $documentType)
    {
        return CustomerEmployeeDocument::where('customer_employee_id', $customerEmployeeId)->where('type', $documentType)->count() > 0;
    }

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "customer_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
