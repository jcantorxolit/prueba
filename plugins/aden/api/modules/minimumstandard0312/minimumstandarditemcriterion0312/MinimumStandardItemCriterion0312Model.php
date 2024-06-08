<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemCriterion0312;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\MinimumStandardItem0312Model;

class MinimumStandardItemCriterion0312Model extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_minimum_standard_item_criterion_0312";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

    public function  getSize()
    {
        return $this->getParameterByValue($this->size, "wg_customer_employee_number");
    }

    public function  getRiskLevel()
    {
        return $this->getParameterByValue($this->riskLevel, "wg_customer_risk_level");
    }

    public function  getItem()
    {
        return MinimumStandardItem0312Model::find($this->minimumStandardItemId);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
