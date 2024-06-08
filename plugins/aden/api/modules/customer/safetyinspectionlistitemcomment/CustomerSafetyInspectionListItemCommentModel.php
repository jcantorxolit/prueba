<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\SafetyInspectionListItemComment;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerSafetyInspectionListItemCommentModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_safety_inspection_list_item_comment";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'created_by', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updated_by', 'otherKey' => 'id']
    ];

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}