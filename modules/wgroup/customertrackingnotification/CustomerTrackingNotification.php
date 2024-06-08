<?php

namespace Wgroup\CustomerTrackingNotification;

use BackendAuth;
use Log;
use RainLab\User\Models\User;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

/**
 * Idea Model
 */
class CustomerTrackingNotification extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_tracking_notification';

    public $belongsTo = [
        'agent' => ['Wgroup\Models\Agent', 'key' => 'user_id', 'otherKey' => 'id'],
        'user' => ['Wgroup\CustomerUser\CustomerUser', 'key' => 'user_id', 'otherKey' => 'id'],
    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public function getUser()
    {
        $model = $this->user;
        $userEntity = User::find($model->user_id);
        $user = new \stdClass();
        $user->id = $model->id;
        $user->name = $userEntity->name .  " " . $userEntity->surname;
        $user->email = $userEntity->email;
        $user->type = $this->type;

        return $user;
    }

    public function getAgent()
    {
        $model = DB::table('wg_agent')
            ->leftJoin('users', 'wg_agent.user_id', '=', 'users.id')
            ->select('wg_agent.name', 'wg_agent.id', 'users.email')
            ->where('wg_agent.id', $this->user_id)
            ->first();

        $user = new \stdClass();
        $user->id = $model->id;
        $user->name = $model->name;
        $user->email = $model->email;
        $user->type = $this->type;

        return $user;
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "tracking_alert_type");
    }

    public function  getTimeType()
    {
        return $this->getParameterByValue($this->timeType, "tracking_alert_timeType");
    }

    public function  getPreference()
    {
        return $this->getParameterByValue($this->preference, "tracking_alert_preference");
    }

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "tracking_alert_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
