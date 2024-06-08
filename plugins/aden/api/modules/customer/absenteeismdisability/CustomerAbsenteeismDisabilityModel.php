<?php

namespace AdeN\Api\Modules\Customer\AbsenteeismDisability;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerAbsenteeismDisabilityModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_absenteeism_disability";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];


	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getImprovementRelation($table, $entityName = null)
    {
        $entityName = $entityName ? $entityName : 'AD';

        return "(SELECT entityId, COUNT(*) qty FROM wg_customer_improvement_plan
        WHERE entityName = '$entityName'
        GROUP BY entityId) $table ";
    }

    public static function getDocumentAndTypeRelation($table, $type)
    {
        return "(SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = '$type' GROUP BY customer_disability_id) $table ";
    }

    public static function getReportATRelation($table)
    {
        return "(SELECT COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al GROUP BY customer_disability_id) $table ";
    }

    public static function getActionPlanRelation($table)
    {
        return "(SELECT COUNT(*) qty, id, customer_disability_id FROM wg_customer_absenteeism_disability_action_plan GROUP BY customer_disability_id) $table ";
    }

}
