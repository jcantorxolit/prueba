<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

/**
 * Idea Model
 */
class CustomerTracking extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_tracking';

    public $belongsTo = [
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
        'user' => ['Wgroup\CustomerUser\CustomerUser', 'key' => 'agent_id', 'otherKey' => 'id'],
        //'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'alerts' => ['Wgroup\Models\CustomerTrackingAlert'],
        'notifications' => ['Wgroup\CustomerTrackingNotification\CustomerTrackingNotification'],
        'comments' => ['Wgroup\Models\CustomerTrackingComment'],
    ];

    public function creator()
    {
        return DB::table("users")->where("id", $this->createdBy)->first();
    }

    public function getUser()
    {
        $model = $this->user;

        $user = new \stdClass();
        $user->id = $model ? $model->id : 0;
        $user->name = $model ? $model->fullName : null;
        $user->email = $model ? $model->email : null;
        $user->type = $this->userType;

        return $user;
    }

    public function getAgent()
    {
        $model = DB::table('wg_agent')
            ->leftJoin('users', 'wg_agent.user_id', '=', 'users.id')
            ->select('wg_agent.name', 'wg_agent.id', 'users.email')
            ->where('wg_agent.id', $this->agent_id)
            ->first();

        $user = new \stdClass();
        $user->id = $model->id;
        $user->name = $model->name;
        $user->email = $model->email;
        $user->type = $this->userType;

        return $user;
    }

    public function getAlerts()
    {
        return CustomerTrackingAlert::whereCustomerTrackingId($this->id)->get();
    }

    public function getTrackingType()
    {
        return $this->getParameterByValue($this->type, "tracking_tiposeg");
    }

    public function getStatusType()
    {
        return $this->getParameterByValue($this->status, "tracking_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
