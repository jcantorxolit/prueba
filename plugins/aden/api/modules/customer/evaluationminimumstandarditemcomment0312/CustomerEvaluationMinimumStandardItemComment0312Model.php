<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEvaluationMinimumStandardItemComment0312Model extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_evaluation_minimum_standard_item_comment_0312";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [];

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}