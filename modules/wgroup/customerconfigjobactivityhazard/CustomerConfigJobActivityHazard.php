<?php

namespace Wgroup\CustomerConfigJobActivityHazard;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfig\ConfigGeneral;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityIntervention;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionDTO;

/**
 * Idea Model
 */
class CustomerConfigJobActivityHazard extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_job_activity_hazard';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'activity' => ['Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivity', 'key' => 'job_activity_id', 'otherKey' => 'id'],
        'classificationModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardClassification', 'key' => 'classification', 'otherKey' => 'id'],
        'descriptionModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardDescription', 'key' => 'description', 'otherKey' => 'id'],
        'typeModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardType', 'key' => 'type', 'otherKey' => 'id'],
        'effectModel' => ['Wgroup\CustomerConfig\ConfigJobActivityHazardEffect', 'key' => 'health_effect', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public  function getInterventions(){
        return  CustomerConfigJobActivityIntervention::whereJobActivityHazardId($this->id)->get();
    }

    public function deleteInterventions()
    {
        CustomerConfigJobActivityIntervention::whereJobActivityHazardId($this->id)->delete();
    }

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    public function  getControlMethod(){
        return $this->getParameterByValue($this->control_method, "config_control_method");
    }

    public function  getMeasureND(){
        return $this->getConfigByValue($this->measure_nd, "ND");
    }

    public function  getMeasureNE(){
        return $this->getConfigByValue($this->measure_ne, "NE");
    }

    public function  getMeasureNC(){
        return $this->getConfigByValue($this->measure_nc, "NC");
    }

    protected  function getConfigByValue($value, $type = ""){
        return  ConfigGeneral::whereId($value)->first();
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
