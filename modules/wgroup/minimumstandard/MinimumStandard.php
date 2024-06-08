<?php

namespace Wgroup\MinimumStandard;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\ConfigMinimumStandardCycle\ConfigMinimumStandardCycle;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;

/**
 * Idea Model
 */
class MinimumStandard extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_minimum_standard';

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
        'item' => ['Wgroup\MinimumStandardItem\MinimumStandardItem', 'key' => 'minimum_standard_id', 'otherKey' => 'id'],
    ];

    public function children()
    {
        return $this->hasMany('Wgroup\MinimumStandard\MinimumStandard', 'parent_id', 'id');
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "minimum_standard_type");
    }

    public function getCycle()
    {
        return ConfigMinimumStandardCycle::whereId($this->cycle_id)->first();
    }

    public function getParent()
    {
        return $this->type == 'C' ? MinimumStandard::find($this->parent_id) : null;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getRelationTable($table)
    {
        return "(SELECT id, numeral, description FROM wg_minimum_standard WHERE isActive = 1) $table ";
    }
}
