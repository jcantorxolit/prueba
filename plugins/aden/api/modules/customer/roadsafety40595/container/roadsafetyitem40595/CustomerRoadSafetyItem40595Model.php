<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItem40595;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerRoadSafetyItem40595Model extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_road_safety_item_40595";

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
        return DB::table('wg_road_safety_rate_40595')
            ->select(
                'wg_road_safety_rate_40595.id',
                'wg_road_safety_rate_40595.code',
                'wg_road_safety_rate_40595.color',
                'wg_road_safety_rate_40595.highlightColor',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value'
            )
            ->where('wg_road_safety_rate_40595.id', $this->rateId)
            ->first();
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
