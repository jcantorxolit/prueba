<?php

namespace Wgroup\CustomerInternalCertificateGradeAgent;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\AgentDTO;

/**
 * Idea Model
 */
class CustomerInternalCertificateGradeAgent extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_certificate_grade_agent';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'grade' => ['Wgroup\CustomerInternalCertificateGrade\CustomerInternalCertificateGrade', 'key' => 'customer_internal_certificate_grade_id', 'otherKey' => 'id'],
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];

    public function  getAgent()
    {
        return AgentDTO::parse($this->agent);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
