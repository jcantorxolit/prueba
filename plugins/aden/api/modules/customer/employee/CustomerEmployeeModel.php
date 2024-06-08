<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Employee;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEmployeeModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_employee";

    public $belongsTo = [
        'employee' => ['Wgroup\Employee\Employee', 'key' => 'employee_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'city' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

    public function getEmployeeEntity()
    {
        $employee = $this->employee;
        if ($employee) {
            $employee->gender = $this->getParameterByValue($employee->gender, 'gender');
        }
        return $employee;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getInfoDetailTable($type)
    {
        return str_replace('\\', '\\\\', "(SELECT MIN(`value`) `value`, entityId, entityName FROM wg_employee_info_detail WHERE entityName = 'Wgroup\\Employee\\Employee' AND type = '$type'
						GROUP BY entityId, entityName, type) $type");
    }

    public static function getRelationInfoDetail($table, $entityId = null)
    {
        $whereEntityId = $entityId ? " AND entityId = $entityId" : '';

        return str_replace('\\', '\\\\', "( SELECT
            MIN(CASE WHEN type = 'cel' THEN `value` END) mobile,
            MIN(CASE WHEN type = 'tel' THEN `value` END) telephone,
            MIN(CASE WHEN type = 'email' THEN `value` END) email,
            MIN(CASE WHEN type = 'dir' THEN `value` END) address,
            MIN(CASE WHEN type = 'fax' THEN `value` END) fax,
            entityId
        FROM
        wg_employee_info_detail WHERE id IN (SELECT MIN(id) FROM wg_employee_info_detail GROUP BY entityId, type) $whereEntityId
        GROUP BY entityId) $table ");
    }

    public static function getRelationInfoDetailByCustomer($table, $customerId = null)
    {
        $whereEntityId = $customerId ? " AND `wg_customer_employee`.customer_id = $customerId" : '';

        return str_replace('\\', '\\\\', "( SELECT
            MIN(CASE WHEN wg_employee_info_detail.type = 'cel' THEN `value` END) mobile,
            MIN(CASE WHEN wg_employee_info_detail.type = 'tel' THEN `value` END) telephone,
            MIN(CASE WHEN wg_employee_info_detail.type = 'email' THEN `value` END) email,
            MIN(CASE WHEN wg_employee_info_detail.type = 'dir' THEN `value` END) address,
            MIN(CASE WHEN wg_employee_info_detail.type = 'fax' THEN `value` END) fax,
            entityId
        FROM
        wg_employee_info_detail
        INNER JOIN wg_employee ON wg_employee.id =  wg_employee_info_detail.entityId
        INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id =  wg_employee.id
        WHERE wg_employee_info_detail.id IN (
            SELECT
                MIN(wg_employee_info_detail.id)
            FROM wg_employee_info_detail
			INNER JOIN wg_employee ON wg_employee.id = wg_employee_info_detail.entityId
			INNER JOIN wg_customer_employee ON wg_customer_employee.employee_id = wg_employee.id
			$whereEntityId
            GROUP BY wg_employee_info_detail.entityId, wg_employee_info_detail.type
        ) $whereEntityId
        GROUP BY entityId) $table ");
    }

    public static function getRelationInfoDetailTable($alias)
    {
        return "(SELECT id, `value`, entityId, entityName FROM wg_employee_info_detail) $alias";
    }

    public function getInfoDetailBy($itemId)
    {
        return DB::table('wg_employee_info_detail')
            ->select('value', 'id')
            ->where('id', '=', $itemId)
            ->first();
    }

    public static function getRelationDocumentCount($table)
    {
        return "(SELECT count(*) qryAttachment, customer_employee_id FROM wg_customer_employee_document GROUP BY customer_employee_id) $table ";
    }

    public static function getContactInformation($alias, $entityId)
    {
        return "( SELECT
            MIN(CASE WHEN type = 'cel' THEN `value` END) mobile,
            MIN(CASE WHEN type = 'tel' THEN `value` END) telephone,
            MIN(CASE WHEN type = 'email' THEN `value` END) email,
            MIN(CASE WHEN type = 'dir' THEN `value` END) address,
            MIN(CASE WHEN type = 'fax' THEN `value` END) fax,
            entityId
        FROM
            wg_employee_info_detail
        WHERE
            entityId = $entityId
        GROUP BY
            entityId) $alias ";
    }
}
