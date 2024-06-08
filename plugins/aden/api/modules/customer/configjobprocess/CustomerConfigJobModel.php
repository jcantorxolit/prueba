<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobProcess;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerConfigJobModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_config_job";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public function getWorkplace()
    {
        return DB::table('wg_customer_config_workplace')->where('id', $this->workplace_id)->first();
    }

    public function getMacroprocess()
    {
        return DB::table('wg_customer_config_macro_process')->where('id', $this->macro_process_id)->first();
    }

    public function getProcess()
    {
        return DB::table('wg_customer_config_process')->where('id', $this->process_id)->first();
    }

    public function getJobData()
    {
        return DB::table('wg_customer_config_job_data')->where('id', $this->job_id)->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
