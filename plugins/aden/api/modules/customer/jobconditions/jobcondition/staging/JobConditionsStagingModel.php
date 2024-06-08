<?php

namespace Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class JobConditionsStagingModel extends Model
{

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition_staging";

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->identification_type, "employee_document_type");
    }

    public function  getWorkModel()
    {  
        return $this->getParameterByValue($this->work_model, "wg_customer_job_conditions_work_model");
    }

    public function getLocation()
    {
        return $this->getParameterByValue($this->location, "wg_customer_job_conditions_location");
    }

    public function getJob(){
        return DB::table('wg_customer_config_job_data')
            ->select(
                'id',
                'name'
            )
            ->where('wg_customer_config_job_data.id', $this->job)
            ->where('status', '=', 'Activo')
            ->first();
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
