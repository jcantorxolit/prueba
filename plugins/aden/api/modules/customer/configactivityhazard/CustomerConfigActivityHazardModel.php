<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityHazard;

use AdeN\Api\Classes\CamelCasing;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

class CustomerConfigActivityHazardModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_config_job";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function getJobData()
    {
        return DB::table('wg_customer_config_job_data')->where('id', $this->jobId)->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getRelationTable($table, $filterOnlyWithHazard = false)
    {
        $where = $filterOnlyWithHazard ? " WHERE wg_customer_config_job_activity_hazard.id > 0 " : "";
        return "(SELECT `wg_customer_config_job_activity_hazard`.*,
		`wg_customer_config_job_activity`.id AS jobActivityId,
		`wg_customer_config_activity`.`id` AS `activityId`,
		`wg_customer_config_job`.`workplace_id`,
		`wg_customer_config_job`.`macro_process_id`,
		`wg_customer_config_job`.`process_id`,
		`wg_customer_config_activity`.`name` AS `activity`,
        `wg_customer_config_job_data`.`name` AS `job`,
        `wg_customer_config_activity_process`.`isRoutine`,
        CASE WHEN wg_customer_config_job_activity_hazard_g.countRecords > 0 THEN 1 ELSE 0 END hasHazards,
        `wg_customer_config_job_activity_hazard_relation`.id AS job_activity_hazard_relation_id
	FROM wg_customer_config_job
    INNER JOIN `wg_customer_config_job_data` ON `wg_customer_config_job_data`.`id` = `wg_customer_config_job`.`job_id`
    -- Se modifica LEFT por INNER ya que presentaba fallo al eliminar registro asociativo
	INNER JOIN wg_customer_config_job_activity ON `wg_customer_config_job_activity`.`job_id` = `wg_customer_config_job`.`id`
	LEFT JOIN wg_customer_config_activity_process ON `wg_customer_config_job_activity`.`activity_id` = `wg_customer_config_activity_process`.`id`
	LEFT JOIN `wg_customer_config_activity` ON `wg_customer_config_activity`.`id` = `wg_customer_config_activity_process`.`activity_id`
	LEFT JOIN `wg_customer_config_job_activity_hazard_relation` ON `wg_customer_config_job_activity_hazard_relation`.`customer_config_job_activity_id` = `wg_customer_config_job_activity`.`id`
    LEFT JOIN `wg_customer_config_job_activity_hazard` ON `wg_customer_config_job_activity_hazard`.`id` = `wg_customer_config_job_activity_hazard_relation`.customer_config_job_activity_hazard_id
    LEFT JOIN (SELECT COUNT(*) countRecords, job_activity_id FROM wg_customer_config_job_activity_hazard GROUP BY job_activity_id) wg_customer_config_job_activity_hazard_g
	ON wg_customer_config_job_activity_hazard_g.job_activity_id = `wg_customer_config_activity`.id $where) $table ";

    }

    public static function getConfigGeneralRelation($table, $type)
    {
        return "(SELECT * FROM `wg_config_general` WHERE `type` = '$type') $table ";
    }
}
