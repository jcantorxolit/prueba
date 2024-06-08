<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Employee\Document;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\Customer\CustomerModel;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEmployeeDocumentModel extends Model
{
	//use CamelCasing;

    const CLASS_NAME = "Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocument";

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_employee_document";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

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

    public function getStatusType()
    {
        return $this->getParameterByValue($this->status, "customer_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getSystemFile()
    {
        return str_replace('\\', '\\\\', "(SELECT * FROM system_files WHERE field = 'document' AND attachment_type = '" . static::CLASS_NAME . "') system_files");
    }

    public static function getRelationTracking($table)
    {
        return "(SELECT customer_employee_document_id, GROUP_CONCAT(observation SEPARATOR '<br>') observation FROM wg_customer_employee_document_tracking GROUP BY customer_employee_document_id) $table ";
    }

    public static function getRelationTrackingMax($table)
    {
        return "(SELECT wg_customer_employee_document_tracking.customer_employee_document_id, observation FROM wg_customer_employee_document_tracking
                INNER JOIN (
                    SELECT MAX(id) AS id, customer_employee_document_id
                    FROM wg_customer_employee_document_tracking GROUP BY customer_employee_document_id
                ) wg_customer_employee_document_tracking_group ON wg_customer_employee_document_tracking_group.id = wg_customer_employee_document_tracking.id
            ) $table ";
    }
}
