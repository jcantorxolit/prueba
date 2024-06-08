<?php

namespace Wgroup\RoadSafety;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\ConfigRoadSafetyCycle\ConfigRoadSafetyCycle;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;

/**
 * Idea Model
 */
class RoadSafety extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_road_safety';

    public $belongsTo = [

    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public $hasMany = [
        'item' => ['Wgroup\RoadSafetyItem\RoadSafetyItem', 'key' => 'road_safety_id', 'otherKey' => 'id'],
    ];

    public function children()
    {
        return $this->hasMany('Wgroup\RoadSafety\RoadSafety', 'parent_id', 'id');
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "road_safety_type");
    }

    public function getCycle()
    {
        return ConfigRoadSafetyCycle::whereId($this->cycle_id)->first();
    }

    public function getParent()
    {
        return $this->type == 'C' ? RoadSafety::find($this->parent_id) : null;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getRelationTable($table)
    {
        return "(SELECT id, numeral, description FROM wg_road_safety WHERE isActive = 1) $table ";
    }
}
