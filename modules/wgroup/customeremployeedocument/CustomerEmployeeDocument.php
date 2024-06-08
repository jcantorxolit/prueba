<?php

namespace Wgroup\CustomerEmployeeDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;
use AdeN\Api\Modules\Customer\CustomerModel;

/**
 * Idea Model
 */
class CustomerEmployeeDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_employee_document';

    public $belongsTo = [];


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
        return DB::table('wg_customers')
            ->join(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {

                $join->on('wg_customers.id', '=', 'document_type.customer_id')
                    ->whereNull('document_type.customer_id', 'or');

            })
            ->select('document_type.*')
            ->where('document_type.value', $requirement)
            ->orderBy('document_type.item')
            ->first();
    }

    public function getRequirement()
    {
        return DB::table('wg_customers')
            ->join(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {

                $join->on('wg_customers.id', '=', 'document_type.customer_id')
                    ->whereNull('document_type.customer_id', 'or');

            })
            ->select('document_type.*')
            // ->where('wg_customers.id', $this->customer_id)
            ->where('document_type.value', $this->requirement)
            ->orderBy('document_type.item')
            ->first();
    }

    public static function hasDocumentType($customerEmployeeId, $documentType)
    {
        return CustomerEmployeeDocument::where('customer_employee_id', $customerEmployeeId)->where('type', $documentType)->count() > 0;
    }

    public function getStatusType()
    {
        return $this->getParameterByValue($this->status, "customer_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected function getCustomerParameterById($value, $group, $ns = "wgroup")
    {
        return CustomerParameterDTO::parse(CustomerParameter::whereNamespace($ns)->whereGroup($group)->whereId($value)->first());
    }

    public function getEmployeeDocumentType($value)
    {
        $query = "
select
  `value` id, '' customerId, namespace, `group`, item `value`, '' `data`, 1 isActive
from
  system_parameters
where namespace = 'wgroup' and `group` = 'wg_employee_attachment' and `value` = :value";

        $whereArray = array();

        $whereArray["value"] = $value;

        $results = DB::select($query, $whereArray);

        return count($results) > 0 ? $results[0] : null;

        //return $this->getCustomerParametersByValue($this->id, "employeeDocumentType");
    }
}
