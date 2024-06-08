<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer;

use AdeN\Api\Helpers\SqlHelper;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameter;
use AdeN\Api\Classes\Criteria;
use AdeN\Api\Modules\Productivity\CustomerProductivityMatrix\CustomerProductivityMatrixRepository;

class CustomerModel extends Model
{
    //use CamelCasing;

    const CLASS_NAME = "Wgroup\Models\Customer";

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customers";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'city' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File'],
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameter::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getDocumentTypeRelation($table)
    {
        return "(SELECT id, null customer_id, item, `value` COLLATE utf8_general_ci AS `value`, 'System' origin FROM system_parameters WHERE namespace = 'wgroup' AND `group` = 'customer_document_type'
        UNION ALL
        SELECT id, customer_id, `value` item, id `value`, 'Customer' origin FROM	wg_customer_parameter
        WHERE namespace = 'wgroup' AND `group` = 'customerDocumentType') $table ";
    }

    public static function getEmployeeDocumentTypeRelation($table)
    {
        return "(SELECT id, null customer_id, item, `value` COLLATE utf8_general_ci AS `value`, 0 isRequired, 'System' origin FROM system_parameters WHERE namespace = 'wgroup' AND `group` = 'wg_employee_attachment'
        UNION ALL
        SELECT id, customer_id, `value` item, id `value`, item isRequired, 'Customer' origin FROM	wg_customer_parameter
        WHERE namespace = 'wgroup' AND `group` = 'employeeDocumentType') $table ";
    }

    public static function getEmployeeDocumentTypeRelationRaw($criteria)
    {
        $q1 = DB::table('system_parameters')
            ->select(
                'system_parameters.id',
                DB::raw("null customer_id"),
                'system_parameters.item',
                DB::raw("value COLLATE utf8_general_ci AS value"),
                DB::raw("0 as isRequired"),
                DB::raw("1 isVisible"),
                DB::raw("'System' as origin")
            )
            ->where('system_parameters.namespace', 'wgroup')
            ->where('system_parameters.group', 'wg_employee_attachment');

        $q2 = DB::table('wg_customer_parameter')
            ->select(
                'wg_customer_parameter.id',
                'wg_customer_parameter.customer_id',
                'wg_customer_parameter.value AS item',
                DB::raw("wg_customer_parameter.id AS value"),
                DB::raw("wg_customer_parameter.item AS isRequired"),
                DB::raw("wg_customer_parameter.is_active AS isVisible"),
                DB::raw("'Customer' AS origin")
            )
            ->where('wg_customer_parameter.namespace', 'wgroup')
            ->where('wg_customer_parameter.group', 'employeeDocumentType');

        if ($criteria != null) {
            if ($criteria instanceof Criteria) {
                if ($criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if ($item->field == 'customerId') {
                            $q2->where('wg_customer_parameter.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        }

                        if ($item->field == 'customerEmployeeId') {
                            $q2->join('wg_customer_employee', function ($join) {
                                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_parameter.customer_id');
                            });
                            $q2->where('wg_customer_employee.id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        }
                    }
                }
            } else {
                if (property_exists($criteria, "customerId")) {
                    $customerId = DB::getPdo()->quote($criteria->customerId);
                    $q2->whereRaw("wg_customer_parameter.customer_id = {$customerId}");
                }
            }
        }

        return $q1->union($q2)->mergeBindings($q2);
    }

    public static function getSystemFile()
    {
        return str_replace('\\', '\\\\', "(SELECT * FROM system_files WHERE attachment_type = '" . static::CLASS_NAME . "') system_files");
    }

    public static function getRelatedAgentAndUser($table)
    {
        return "( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email
        FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) $table ";
    }

    public static function getRelatedAgentAndUserRaw($criteria)
    {
        $q1 = DB::table('wg_agent')
            ->join('wg_customer_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->leftjoin('users', function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->select(
                'wg_agent.id',
                'wg_customer_agent.customer_id',
                'wg_agent.name',
                DB::raw("'Asesor' AS type"),
                DB::raw("users.email COLLATE utf8_general_ci AS email")
            )
            ->groupBy(
                'wg_agent.id',
                'wg_customer_agent.customer_id'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
            })
            ->select(
                'wg_customer_user.id',
                'wg_customer_user.customer_id',
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS fullName"),
                DB::raw("'Cliente Usuario' AS type"),
                'users.email AS email'
            )
            ->groupBy(
                'wg_customer_user.id',
                'wg_customer_user.customer_id'
            );

        if ($criteria != null) {
            if ($criteria instanceof Criteria) {
                if ($criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if ($item->field == 'customerId') {
                            $q1->where('wg_customer_agent.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            $q2->where('wg_customer_user.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        }
                    }
                }
            } else {
                if (property_exists($criteria, "customerId")) {
                    $customerId = DB::getPdo()->quote($criteria->customerId);
                    $q1->whereRaw("wg_customer_agent.customer_id = {$customerId}");
                    $q2->whereRaw("wg_customer_user.customer_id = {$customerId}");
                }
            }
        }

        return $q1->union($q2)->mergeBindings($q2);
    }

    public static function getRelatedUnsafeActAgentAndUserRaw($criteria)
    {
        $q1 = DB::table('wg_agent')
            ->join('wg_customer_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->leftjoin('users', function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->select(
                'wg_agent.id',
                'wg_customer_agent.customer_id',
                'wg_agent.name',
                DB::raw("'agent' AS type"),
                DB::raw("users.email COLLATE utf8_general_ci AS email"),
                'wg_agent.user_id'
            )
            ->groupBy(
                'wg_agent.id',
                'wg_customer_agent.customer_id'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
            })
            ->select(
                'wg_customer_user.id',
                'wg_customer_user.customer_id',
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS fullName"),
                DB::raw("'user' AS type"),
                'users.email AS email',
                'users.id AS user_id'
            )
            ->groupBy(
                'wg_customer_user.id',
                'wg_customer_user.customer_id'
            );

        if ($criteria != null) {
            if ($criteria instanceof Criteria) {
                if ($criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if ($item->field == 'customerId') {
                            $q1->where('wg_customer_agent.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            $q2->where('wg_customer_user.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        }
                    }
                }
            } else {
                if (property_exists($criteria, "customerId")) {
                    $customerId = DB::getPdo()->quote($criteria->customerId);
                    $q1->whereRaw("wg_customer_agent.customer_id = {$customerId}");
                    $q2->whereRaw("wg_customer_user.customer_id = {$customerId}");
                }
            }
        }

        return $q1->union($q2)->mergeBindings($q2);
    }


    public static function findAgentAndUserRaw($customerId, $id, $type)
    {
        $q1 = DB::table('wg_agent')
            ->join('wg_customer_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->leftjoin('users', function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->select(
                'wg_agent.id',
                'wg_customer_agent.customer_id',
                'wg_agent.name',
                DB::raw("'Asesor' AS type"),
                DB::raw("users.email COLLATE utf8_general_ci AS email")
            )
            ->groupBy(
                'wg_agent.id',
                'wg_customer_agent.customer_id'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
            })
            ->select(
                'wg_customer_user.id',
                'wg_customer_user.customer_id',
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS fullName"),
                DB::raw("'Cliente Usuario' AS type"),
                'users.email AS email'
            )
            ->groupBy(
                'wg_customer_user.id',
                'wg_customer_user.customer_id'
            );

        $q1->where('wg_customer_agent.customer_id', '=', $customerId, 'and');
        $q2->where('wg_customer_user.customer_id', '=', $customerId, 'and');

        $q1->union($q2)->mergeBindings($q2);

        return DB::table(DB::raw("({$q1->toSql()}) as responsible"))
            ->mergeBindings($q1)
            ->where('responsible.id', $id)
            ->where('responsible.type', $type)
            ->first();
    }

    public static function getDocumentSecurityAgentAndUserRaw($criteria)
    {
        $q1 = DB::table('wg_agent')
            ->join('wg_customer_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->select(
                'users.id',
                'wg_customer_agent.customer_id',
                'wg_agent.name',
                DB::raw("'Asesor' AS type"),
                DB::raw("users.email COLLATE utf8_general_ci AS email")
            )
            ->groupBy(
                'wg_agent.id',
                'wg_customer_agent.customer_id'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('users', function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
                $join->on('users.company', '=', 'wg_customer_user.customer_id');
            })
            ->select(
                'users.id',
                'wg_customer_user.customer_id',
                'wg_customer_user.fullName',
                DB::raw("CASE WHEN users.wg_type = 'customerAdmin' THEN 'Cliente Admin' WHEN users.wg_type = 'customerUser' THEN 'Cliente Asesor' ELSE 'Desconocido' END AS type"),
                'wg_customer_user.email AS email'
            )
            ->groupBy(
                'wg_customer_user.id',
                'wg_customer_user.customer_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where('wg_customer_agent.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        $q2->where('wg_customer_user.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        return $q1->union($q2);
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
        wg_info_detail WHERE id IN (SELECT MIN(id) FROM wg_info_detail  WHERE (`wg_info_detail`.`entityName` = 'Wgroup\\Models\\Customer') GROUP BY entityId, type) $whereEntityId
        GROUP BY entityId) $table ");
    }

    public static function getParameterRelation($table, $alias = null)
    {
        $alias = $alias && !empty($alias) ? $alias : $table;

        return "(SELECT id, customer_id, `value` item, id `value`, 'Customer' origin FROM	wg_customer_parameter
        WHERE namespace = 'wgroup' AND `group` = '$table') $alias ";
    }

    public static function getParameterRelationRaw($criteria)
    {
        $filterColumns = [
            "customerId" => 'wg_customer_parameter.customer_id',
            "group" => 'wg_customer_parameter.group',
            "item" => 'wg_customer_parameter.item',
        ];

        $query = DB::table('wg_customer_parameter')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_parameter.customer_id');
            })
            ->select(
                'wg_customer_parameter.id',
                'wg_customer_parameter.customer_id',
                'wg_customer_parameter.value AS name'
            )
            ->where('wg_customer_parameter.namespace', 'wgroup');

        if ($criteria != null) {
            if ($criteria instanceof Criteria) {
                if ($criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if (array_key_exists($item->field, $filterColumns)) {
                            $query->where($filterColumns[$item->field], SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        }
                    }
                }
            } else {
                if (property_exists($criteria, "customerId")) {
                    $customerId = DB::getPdo()->quote($criteria->customerId);
                    $query->whereRaw("wg_customer_parameter.customer_id = {$customerId}");
                }
            }
        }

        return $query;
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "customer_classification");
    }

    public function hasSpecialMatrix()
    {
        return DB::table('wg_customer_parameter')
            ->where("group", "specialMatrix")
            ->where("item", 1)
            ->where("customer_id", $this->id)
            ->count() > 0;
    }

    public function hasNotificationUser()
    {
        return DB::table('wg_customer_parameter')
            ->where("group", "userNotification")
            ->where("customer_id", $this->id)
            ->count() > 0;
    }

    public function hasMinimumStandard0312()
    {
        return DB::table('wg_customer_evaluation_minimum_standard_0312')
            ->where("status", "A")
            ->where("customer_id", $this->id)
            ->count() > 0;
    }
}
