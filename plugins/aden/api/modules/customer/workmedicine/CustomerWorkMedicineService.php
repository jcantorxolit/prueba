<?php

namespace AdeN\Api\Modules\Customer\WorkMedicine;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerWorkMedicineService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    //Metodo que trae la información de los examenes ocupacionales del empleado que se esta visualizando
    public function getInfoExportExcel($criteria)
    {
        $customerEmployeeId = $criteria->customerEmployeeId ?? [];

        return DB::table("wg_customer_work_medicine AS cwm")
        ->join("wg_customer_employee AS ce", "ce.id", "=", "cwm.customer_employee_id")
        ->join("wg_employee as employee", "employee.id", "=", "ce.employee_id")
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('work_medicine_medical_concept', 'sp')), function ($join) {
            $join->on('sp.value', '=', 'cwm.medicalConcept');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('work_medicine_examination_type', 'sp1')), function ($join) {
            $join->on('sp1.value', '=', 'cwm.examinationType');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type', 'sp2')), function ($join) {
            $join->on('sp2.value', '=', 'ce.contractType');
        })->leftjoin("wg_customer_config_job as ccj", function ($join) {
            $join->on('ccj.id', '=', 'ce.job');
        })->leftjoin("wg_customer_config_job_data as ccjd", function ($join) {
            $join->on('ccjd.id', '=', 'ccj.job_id');
        })
        ->leftjoin("wg_customer_work_medicine_complementary_test as cwmct", function ($join) {
            $join->on('cwmct.customer_work_medicine_id', '=', 'cwm.id');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('work_medicine_complementary_test', 'sp3')), function ($join) {
            $join->on('sp3.value', '=', 'cwmct.complementaryTest');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('work_medicine_complementary_test_result', 'sp4')), function ($join) {
            $join->on('sp4.id', '=', 'cwmct.result');
        })
            ->where('cwm.customer_employee_id', '=', $customerEmployeeId)
            ->select(
                'employee.firstName as firstName',
                'employee.lastName as lastName',
                'cwm.examinationDate as examinationDate',
                'cwm.occupationalConclusion as conclusiones',
                'cwm.occupationalBehavior as conductas',
                'cwm.generalRecommendation as recomendaciones',
                'sp.item as medicalConcept',
                'sp1.item as examinationType',
                'ccjd.name as job',
                'sp3.item as complementatyTest',
                'sp4.item as result',
                'cwmct.interpretation'
            );
    }
}