<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAl;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerOccupationalInvestigationModel extends Model
{
	//use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_occupational_investigation_al";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];


	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getRelationInfoDetail($table, $entityId = null)
    {
        $whereEntityId = $entityId ? " AND entityId = $entityId" : '';

        return "( SELECT
            MIN(CASE WHEN type = 'cel' THEN `value` END) mobile,
            MIN(CASE WHEN type = 'tel' THEN `value` END) telephone,
            MIN(CASE WHEN type = 'email' THEN `value` END) email,
            MIN(CASE WHEN type = 'dir' THEN `value` END) address,
            MIN(CASE WHEN type = 'fax' THEN `value` END) fax,
            entityId
        FROM
        wg_info_detail WHERE id IN (SELECT MIN(id) FROM wg_info_detail  WHERE (`wg_info_detail`.`entityName` = 'Wgroup\\\\\CustomerOccupationalInvestigationAl\\\\CustomerOccupationalInvestigationAl') GROUP BY entityId, type) $whereEntityId
        GROUP BY entityId) $table ";
    }
}
