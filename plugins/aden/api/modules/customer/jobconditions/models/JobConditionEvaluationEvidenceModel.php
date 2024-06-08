<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Models;

use October\Rain\Database\Model;

class JobConditionEvaluationEvidenceModel extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition_self_evaluation_evidences";

    
    public $attachMany = [
        'photos' => [ 'System\Models\File' ]
    ];

}
