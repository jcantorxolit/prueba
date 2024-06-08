<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobActivity;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use AdeN\Api\Modules\Customer\ConfigJobProcess\CustomerConfigJobModel;
use AdeN\Api\Modules\Customer\ConfigActivityProcess\CustomerConfigActivityProcessModel;

class CustomerConfigJobActivityModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_config_job_activity";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

    public $hasMany = [
        'hazardRelation' => ['AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\CustomerConfigJobActivityHazardRelationModel', 'key' => 'customer_config_job_activity_id', 'otherKey' => 'id'],
    ];

    public function getJobProcess()
    {
        return CustomerConfigJobModel::find($this->job_id);
    }

    public function getActivityProcess()
    {
        return CustomerConfigActivityProcessModel::find($this->activity_id);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
