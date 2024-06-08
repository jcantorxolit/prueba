<?php

namespace Wgroup\CustomerAbsenteeismDisability;

use BackendAuth;
use Carbon\Carbon;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerAbsenteeismDisabilityActionPlan\CustomerAbsenteeismDisabilityActionPlan;
use Wgroup\CustomerAbsenteeismDisabilityReportAL\CustomerAbsenteeismDisabilityReportAL;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCost;
use Wgroup\SystemParameter\SystemParameter;

/**
 * Idea Model
 */
class CustomerAbsenteeismDisability extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_absenteeism_disability';

    public $belongsTo = [
        'employee' => ['Wgroup\CustomerEmployee\CustomerEmployee', 'key' => 'customer_employee_id', 'otherKey' => 'id'],
        'diagnostic' => ['Wgroup\DisabilityDiagnostic\DisabilityDiagnostic', 'key' => 'diagnostic_id', 'otherKey' => 'id'],
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

    public function  getIndirectCost()
    {
        return CustomerAbsenteeismIndirectCost::whereCustomerDisabilityId($this->id)->get();
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "absenteeism_disability_type");
    }

    public function  getCause()
    {
        if ($this->category == 'Administrativo') {
            return $this->getParameterByValue($this->cause, "absenteeism_disability_causes_admin");
        } else {
            return $this->getParameterByValue($this->cause, "absenteeism_disability_causes");
        }
    }

    public function  getCategory()
    {
        return $this->getParameterByValue($this->category, "absenteeism_category");
    }

    public function  getAccidentType()
    {
        return $this->getParameterByValue($this->accidentType, "absenteeism_disability_accident_type");
    }

    public function getWorkplace()
    {
        return DB::table('wg_customer_config_workplace')->find($this->workplace_id);
    }

    public function getDisabilityParent()
    {
        $entity = $this->query()
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes_admin')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes_admin.value');
        })->select(
            'wg_customer_absenteeism_disability.id',
            DB::raw("CASE WHEN absenteeism_disability_causes.item IS NOT NULL THEN absenteeism_disability_causes.item ELSE absenteeism_disability_causes_admin.item END AS cause"),
            DB::raw("DATE_FORMAT(wg_customer_absenteeism_disability.start, '%d/%m/%Y') AS start")
        )->where('wg_customer_absenteeism_disability.id', $this->customer_absenteeism_disability_parent_id)
        ->first();

        return $entity ? [
            'id' => $entity->id,
            'name' => "{$entity->start} | {$entity->cause}"
        ] : null;
    }

    public function  getActionPlan()
    {
        return CustomerAbsenteeismDisabilityActionPlan::whereCustomerDisabilityId($this->id)->first();
    }

    public function  hasReport()
    {
        return CustomerAbsenteeismDisabilityReportAL::whereCustomerDisabilityId($this->id)->count() > 0;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
