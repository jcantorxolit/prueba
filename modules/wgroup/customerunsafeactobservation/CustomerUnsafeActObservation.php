<?php

namespace Wgroup\CustomerUnsafeActObservation;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlace;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;

/**
 * Idea Model
 */
class CustomerUnsafeActObservation extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_unsafe_act_observation';

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'image' => ['System\Models\File']
    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "customer_unsafe_act_status");
    }

    public function  getRiskType()
    {
        return $this->getParameterByValue($this->risk_type, "customer_unsafe_act_risk_type");
    }

    public function  getWorkPlace()
    {
        return CustomerConfigWorkPlaceDTO::parse(CustomerConfigWorkPlace::find($this->work_place));
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
