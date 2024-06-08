<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use AdeN\Api\Modules\MinimumStandard0312\MinimumStandard0312Model;
use AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemDetail0312\MinimumStandardItemDetail0312Model;

class MinimumStandardItem0312Model extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_minimum_standard_item_0312";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

    private function getminimumStandardModel($id)
    {
        return MinimumStandard0312Model::find($id);
    }

    public function getminimumStandard()
    {
        return $this->getminimumStandardModel($this->minimumStandardId);
    }

    public function getminimumStandardParent($model)
    {
        if (!$model) return null;

        return $this->getminimumStandardModel($model->parentId);
    }

    public function getLegalFrameworkList()
    {
        return MinimumStandardItemDetail0312Model::where('minimum_standard_item_id', $this->id)
            ->where('type', 'legal-framework')
            ->get();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
