<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Jobcondition;

use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;
use System\Models\Parameters;use Wgroup\SystemParameter\SystemParameter;

class JobConditionModel extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition";

    public function getEmployee()
    {
        $employee = $this->getDataEmployee($this->customer_employee_id);

        $employee->gender = $this->getParameterByValue($employee->gender, "gender");
        $employee->documentType = $this->getParameterByValue($employee->documentType, "employee_document_type");
        return $employee;
    }

    public function getBoss()
    {
        return $this->getDataEmployee($this->immediate_boss_id);
    }

    private function getDataEmployee($customerEmployeeId)
    {
        return DB::table('wg_customer_employee as ce')
            ->join('wg_employee as e', 'e.id', '=', 'ce.employee_id')
            ->join(DB::raw(SystemParameter::getRelationTable('gender')), function ($join) {
                $join->on('e.gender', '=', 'gender.value');
            })
            ->where('ce.id', $customerEmployeeId)
            ->select(
                'ce.employee_id as id',
                'ce.customer_id as customerId',
                'e.documentType',
                'e.documentNumber',
                'e.fullName',
                'e.gender'
            )
            ->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
