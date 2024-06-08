<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Models;

use October\Rain\Database\Model;

class JobConditionEvaluationAnswerModel extends Model
{

    const COMPLY   = 'JCA001';
    const NOCOMPLY = 'JCA002';
    const NOAPPLY  = 'JCA003';

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition_self_evaluation_answers";

}
