<?php

namespace Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging;

use AdeN\Api\Classes\BaseService;
use DB;

class JobConditionsStagingService extends BaseService
{
    public function getEmployeeForCustomer($criteria)
    {
        return DB::table('wg_employee')
            ->join('wg_customer_employee', 'wg_customer_employee.employee_id', '=', 'wg_employee.id')
            ->where('wg_employee.documentType', '=', $criteria->documentType->value)
            ->where('wg_employee.documentNumber', '=', $criteria->documentNumber)
            ->where('wg_customer_employee.customer_id', $criteria->customerId)
            ->value('wg_employee.id');
    }

    public function getAutoevalForLocation($criteria)
    {
        return DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as condition', 'eval.job_condition_id', '=', 'condition.id')
            ->join('wg_customer_employee as ce', 'condition.customer_employee_id', '=', 'ce.id')
            ->join('wg_employee as employee', 'ce.employee_id', '=', 'employee.id')
            ->where('employee.documentType', '=', $criteria->documentType->value)
            ->where('employee.documentNumber', '=', $criteria->documentNumber)
            ->where('condition.customer_id', $criteria->customerId)
            ->where('eval.location', $criteria->location->value)
            ->where('eval.state', 1)
            ->value(DB::raw('COUNT(eval.id)'));
    }

    public function getAutoevalForLocationStaging($criteria)
    {
        return DB::table('wg_customer_job_condition_staging')
            ->where('customer_id', $criteria->customerId)
            ->where('identification_type', '=', $criteria->documentType->value)
            ->where('document_number', '=', $criteria->documentNumber)
            ->where('location', $criteria->location->value)
            ->where('session_id', $criteria->sessionId)
            ->where('id','<>',$criteria->id)
            ->value(DB::raw('CASE WHEN COUNT(*) THEN 1 ELSE 0 END AS "location"'));
    }
}
