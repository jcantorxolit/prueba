<?php

namespace Wgroup\CustomerUnsafeAct;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfig\ConfigJobActivityHazardClassification;
use Wgroup\CustomerConfig\ConfigJobActivityHazardType;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlace;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use DB;
use Wgroup\Models\Customer;

/**
 * Idea Model
 */
class CustomerUnsafeAct extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_unsafe_act';

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
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

    public $attachMany = [
        'photos' => ['System\Models\File']
    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "customer_unsafe_act_status");
    }

    public function  getRiskType()
    {
        return ConfigJobActivityHazardClassification::find($this->risk_type);
    }

    public function  getClassification()
    {
        return ConfigJobActivityHazardType::find($this->classification_id);
    }

    public function  getWorkPlace()
    {
        return CustomerConfigWorkPlaceDTO::parse(CustomerConfigWorkPlace::find($this->work_place));
    }

    public function  getResponsible()
    {
        $type = $this->responsible_type == "agent" ? "Asesor" : "Cliente Usuario";
        return Customer::getAgentOrUser($this->responsible_id, $type);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
