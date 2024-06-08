<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem0312;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEvaluationMinimumStandardItem0312Model extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_evaluation_minimum_standard_item_0312";

    public $belongsTo = [
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

    public function getRate()
    {
        return DB::table('wg_config_minimum_standard_rate_0312')
            ->select(
                'wg_config_minimum_standard_rate_0312.id',
                'wg_config_minimum_standard_rate_0312.code',
                'wg_config_minimum_standard_rate_0312.color',
                'wg_config_minimum_standard_rate_0312.highlightColor',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value'
            )
            ->where('wg_config_minimum_standard_rate_0312.id', $this->rateId)
            ->first();
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
