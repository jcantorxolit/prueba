<?php

namespace AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerAbsenteeismDisabilityStagingService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findCustomerEmployee($id)
    {
        $data = DB::table('wg_customer_employee')
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
    
            })->leftjoin("wg_customer_config_job", function ($join) {
                $join->on('wg_customer_employee.job', '=', 'wg_customer_config_job.id');
    
            })->leftjoin("wg_customer_config_job_data", function ($join) {
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
    
            })            
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
                $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');
            })
            ->select(
                'wg_customer_employee.id',
                'wg_customer_employee.contractType',
                "employee_document_type.item as employeeDocumentType",
                'wg_employee.documentType',
                "wg_employee.documentNumber",
                "wg_employee.firstName",
                "wg_employee.lastName",
                "wg_employee.fullName",
                "employee_contract_type.item AS contractTypeItem",
                "wg_customer_config_workplace.name as workPlace",
                "wg_customer_config_job_data.name as job",
                "wg_customer_employee.salary"
            )
            ->where('wg_customer_employee.id', $id)
            ->first();

        return $data ? [
            'id' => $data->id,
            'entity' => [
                'documentNumber' => $data->documentNumber,
                'fullName' => $data->fullName,
                'firstName' => $data->firstName,
                'lastName' => $data->lastName,
                'documentType' => [
                    "item" => $data->employeeDocumentType,
                    'value' => $data->documentType
                ]
            ],
            'workPlace' => [
                'name' => $data->workPlace,
            ],
            'job' => [
                'name' => $data->job,
            ],
            'salary' => $data->salary,
            "contractType" => [
                "item" => $data->contractTypeItem,
                'value' => $data->contractType
            ]
        ] : null;
    }

    public function findDiagnostic($code)
    {
        $data = DB::table('wg_disability_diagnostic')           
            ->select(
                'wg_disability_diagnostic.id',
                'wg_disability_diagnostic.code',
                'wg_disability_diagnostic.description'                
            )
            ->where('wg_disability_diagnostic.code', $code)
            ->first();

        return $data ? [
            'id' => $data->id,
            'code' => $data->code,
            'description' => $data->description            
        ] : null;
    }

    public function findBodyPart($name)
    {
        $data = DB::table('wg_config_day_charged_part')           
            ->select(
                'wg_config_day_charged_part.id',
                'wg_config_day_charged_part.name',
                'wg_config_day_charged_part.value'                
            )
            ->where('wg_config_day_charged_part.name', $name)
            ->first();

        return $data ? [
            'id' => $data->id,
            'name' => $data->name,
            'value' => $data->value            
        ] : null;
    }

    public function findBodyPartById($id)
    {
        $data = DB::table('wg_config_day_charged_part')           
            ->select(
                'wg_config_day_charged_part.id',
                'wg_config_day_charged_part.name',
                'wg_config_day_charged_part.value'                
            )
            ->where('wg_config_day_charged_part.id', $id)
            ->first();

        return $data ? [
            'id' => $data->id,
            'name' => $data->name,
            'value' => $data->value            
        ] : null;
    }
}
