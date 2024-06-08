<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\InvestigationAl;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerInvestigationAlModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_investigation_al";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'created_by', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updated_by', 'otherKey' => 'id']
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getAllofTable($table, $alias = '')
    {
        $alias = trim($alias) != '' ? $alias : $table;
        return "(SELECT * FROM $table) $alias ";
    }

    public static function getCustomerAddress($table, $alias = '')
    {
        $alias = trim($alias) != '' ? $alias : $table;
        return "(SELECT MIN(`value`) `value`, entityId, entityName FROM $table WHERE entityName = 'Wgroup\Models\Models' AND type = 'dir' GROUP BY entityId, entityName, type) $alias ";
    }

    public static function getControlDates($table, $alias = '')
    {
        $alias = trim($alias) != '' ? $alias : $table;
        return "(SELECT customer_investigation_id, MAX(CASE	WHEN controlType = 'date_letter_recommendation' THEN dateValue END) date_letter_recommendation, MAX(CASE WHEN controlType = 'date_ia_customer' THEN	dateValue END ) date_ia_customer FROM $table GROUP BY customer_investigation_id) $alias ";
    }

    public static function getMeasure($table, $alias = '')
    {
        $alias = trim($alias) != '' ? $alias : $table;
        return "(SELECT m.customer_investigation_id, m.checkDate, mt.dateOf, investigation_measure_tracking_status.`item` `status`, mt.implementationDate, mt.description, mt.justification, mt.`comment` FROM $table m	LEFT JOIN wg_customer_investigation_al_measure_tracking mt ON m.id = mt.customer_investigation_measure_id LEFT JOIN (SELECT * FROM system_parameters WHERE `group` = 'investigation_measure_tracking_status') investigation_measure_tracking_status ON mt.`status` COLLATE utf8_general_ci = investigation_measure_tracking_status.value) $alias ";
    }

}
