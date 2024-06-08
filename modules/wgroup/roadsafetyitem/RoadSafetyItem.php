<?php

namespace Wgroup\RoadSafetyItem;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\RoadSafety\RoadSafetyDTO;
use Wgroup\RoadSafetyItemDetail\RoadSafetyItemDetail;
use Wgroup\RoadSafetyItemDetail\RoadSafetyItemDetailDTO;
use DB;

/**
 * Idea Model
 */
class RoadSafetyItem extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_road_safety_item';

    public $belongsTo = [
        'roadSafety' => ['Wgroup\RoadSafety\RoadSafety', 'key' => 'road_safety_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
    ];

    public $hasMany = [
    ];

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function getRoadSafety()
    {
        return RoadSafetyDTO::parse($this->roadSafety);
    }

    public function getLegalFramework()
    {
        return RoadSafetyItemDetailDTO::parse(RoadSafetyItemDetail::whereRoadSafetyItemId($this->id)->whereType('legal-framework')->get());
    }

    public function getVerificationMode()
    {
        return RoadSafetyItemDetailDTO::parse(RoadSafetyItemDetail::whereRoadSafetyItemId($this->id)->whereType('verification-mode')->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
