<?php

namespace Wgroup\CustomerHealthDamageQualificationSourceDocument;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnostic;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationSourceDocument extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_qs_document';

    public $belongsTo = [
        'medicine' => ['Wgroup\WorkMedicine\WorkMedicine', 'key' => 'customer_work_medicine_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "work_health_damage_qs_document_type");
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "work_health_damage_document_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
