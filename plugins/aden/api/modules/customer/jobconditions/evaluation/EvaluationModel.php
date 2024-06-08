<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Evaluation;

use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionWorkplaceModel;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

class EvaluationModel extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition_self_evaluation";

    public function getWorkModel() {
        return $this->getParameterByValue($this->work_model, "wg_customer_job_conditions_work_model");
    }

    public function getLocation() {
        return $this->getParameterByValue($this->location, "wg_customer_job_conditions_location");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public function getWorkplace() {
        return JobConditionWorkplaceModel::find($this->workplace_id)->name;
    }

    public function getOccupation() {
        return DB::table('wg_customer_config_job_data')->where('id', $this->occupationId)->first();
    }

}
