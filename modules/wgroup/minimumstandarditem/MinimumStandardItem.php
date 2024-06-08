<?php

namespace Wgroup\MinimumStandardItem;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\MinimumStandard\MinimumStandardDTO;
use Wgroup\MinimumStandardItemDetail\MinimumStandardItemDetail;
use Wgroup\MinimumStandardItemDetail\MinimumStandardItemDetailDTO;
use DB;

/**
 * Idea Model
 */
class MinimumStandardItem extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_minimum_standard_item';

    public $belongsTo = [
        'minimumStandard' => ['Wgroup\MinimumStandard\MinimumStandard', 'key' => 'minimum_standard_id', 'otherKey' => 'id'],
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

    public function getMinimumStandard()
    {
        return MinimumStandardDTO::parse($this->minimumStandard);
    }

    public function getLegalFramework()
    {
        return MinimumStandardItemDetailDTO::parse(MinimumStandardItemDetail::whereMinimumStandardItemId($this->id)->whereType('legal-framework')->get());
    }

    public function getVerificationMode()
    {
        return MinimumStandardItemDetailDTO::parse(MinimumStandardItemDetail::whereMinimumStandardItemId($this->id)->whereType('verification-mode')->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
