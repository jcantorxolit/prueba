<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobActivityStaging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CustomerRepository;
use DB;
use Excel;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigJobActivityStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigJobActivityStagingModel());

        $this->service = new CustomerConfigJobActivityStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_staging.id",
            "customerId" => "wg_customer_config_job_activity_staging.customer_id",
            "workplaceId" => "wg_customer_config_job_activity_staging.workplace_id",
            "workplace" => "wg_customer_config_job_activity_staging.workplace",
            "macroProcessId" => "wg_customer_config_job_activity_staging.macro_process_id",
            "macroprocess" => "wg_customer_config_job_activity_staging.macroprocess",
            "processId" => "wg_customer_config_job_activity_staging.process_id",
            "process" => "wg_customer_config_job_activity_staging.process",
            "jobId" => "wg_customer_config_job_activity_staging.job_id",
            "job" => "wg_customer_config_job_activity_staging.job",
            "activityId" => "wg_customer_config_job_activity_staging.activity_id",
            "activity" => "wg_customer_config_job_activity_staging.activity",
            "isRoutine" => "wg_customer_config_job_activity_staging.is_routine",
            "isValid" => "wg_customer_config_job_activity_staging.is_valid",
            "observation" => "wg_customer_config_job_activity_staging.observation",
            "index" => "wg_customer_config_job_activity_staging.index",
            "sessionId" => "wg_customer_config_job_activity_staging.session_id",
            "createdBy" => "wg_customer_config_job_activity_staging.created_by",
            "createdAt" => "wg_customer_config_job_activity_staging.created_at",
            "updatedAt" => "wg_customer_config_job_activity_staging.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_job_activity_staging.parent_id', '=', 'tableParent.id');
		}
		*/


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            throw new \Exception('Record not found');        
        }

        $authUser = $this->getAuthUser();

        $entityModel->customerId = $entity->customerId;
        $entityModel->workplaceId = $entity->workplace ? $entity->workplace->id : null;
        $entityModel->workplace = $entity->workplace ? $entity->workplace->name : null;
        $entityModel->macroProcessId = $entity->macroprocess ? $entity->macroprocess->id : null;
        $entityModel->macroprocess = $entity->macroprocess ? $entity->macroprocess->name : null;
        $entityModel->processId = $entity->process ? $entity->process->id : null;
        $entityModel->process = $entity->process ? $entity->process->name : null;
        $entityModel->jobId = $entity->job ? $entity->job->id : null;
        $entityModel->job = $entity->job ? $entity->job->name : null;
        $entityModel->activityId = $entity->activity ? $entity->activity->id : null;
        $entityModel->activity = $entity->activity ? $entity->activity->name : null;
        $entityModel->isRoutine = $entity->isRoutine ? $entity->isRoutine->value : null;
        $entityModel->observation = null;
        $entityModel->index = $entity->index;
        $entityModel->sessionId = $entity->sessionId;
        $entityModel->createdBy = $authUser ? $authUser->id : $entityModel->createdBy;
        $entityModel->updatedBy = $authUser ? $authUser->id : $entityModel->updatedBy;
        $entityModel->isValid = true;

        $entityModel->save();

        DB::statement("CALL TL_Job_Activity_Staging({$entity->customerId}, '$entityModel->session_id')");

        return $this->parseModelWithRelations($entityModel);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;            
            $entity->workplace = $this->service->findWorkplace($model->workplaceId);
            $entity->macroprocess = $this->service->findMacroprocess($model->macroProcessId);
            $entity->process = $this->service->findProcess($model->processId);
            $entity->job = $this->service->findJob($model->jobId);
            $entity->activity = $this->service->findActivity($model->activityId);            
            $entity->isRoutine = [ "name" => $model->isRoutine, "value" => $model->isRoutine ];  ;
            $entity->observation = $model->observation;
            $entity->index = $model->index;
            $entity->sessionId = $model->sessionId;      
            $entity->isValid = $model->isValid == 1;

            return $entity;
        } else {
            return null;
        }
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/PlantillaCargosActividades.xlsx";        

        $workplaceData = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, (new CustomerRepository)->getWorkplaceList($customerId));

        $macroprocess = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, $this->service->getMacroprocessList($customerId));    
        
        $process = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, $this->service->getProcessList($customerId));  

        $job = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, $this->service->getJobList($customerId));  

        $activity = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, $this->service->getActivityList($customerId));       
        
        $workplace = CmsHelper::prependEmptyItemInArray($workplaceData);
        $macroprocess = CmsHelper::prependEmptyItemInArray($macroprocess);
        $process = CmsHelper::prependEmptyItemInArray($process);
        $job = CmsHelper::prependEmptyItemInArray($job);
        $activity = CmsHelper::prependEmptyItemInArray($activity);

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($workplace, $macroprocess, $process, $job, $activity) {
            $sheet = $file->setActiveSheetIndex(1);
            
            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'CentroTrabajo', 
                    $file->getActiveSheet(), 
                    'A1:A'. count($workplace)
                )
            );

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'Macroproceso', 
                    $file->getActiveSheet(), 
                    'B1:B'. count($macroprocess)
                )
            );

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'Proceso', 
                    $file->getActiveSheet(), 
                    'C1:C'. count($process)
                )
            );

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'Cargo', 
                    $file->getActiveSheet(), 
                    'D1:D'. count($job)
                )
            );

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'Actividad', 
                    $file->getActiveSheet(), 
                    'E1:E'. count($activity)
                )
            );
 
            $sheet->fromArray($workplace, null, 'A1', false);
            $sheet->fromArray($macroprocess, null, 'B1', false);
            $sheet->fromArray($process, null, 'C1', false);
            $sheet->fromArray($job, null, 'D1', false);
            $sheet->fromArray($activity, null, 'E1', false);

            $sheet = $file->setActiveSheetIndex(0);
            
            $cels = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'CentroTrabajo'],
                'B2' => ['range' => 'B2:B5000', 'formula' => 'Macroproceso'],
                'C2' => ['range' => 'C2:C5000', 'formula' => 'Proceso'],
                'D2' => ['range' => 'D2:D5000', 'formula' => 'Cargo'],
                'E2' => ['range' => 'E2:E5000', 'formula' => 'Actividad'],
                'F2' => ['range' => 'F2:F5000', 'formula' => 'Estado']
            ];

            foreach ($cels as $cell => $info) {
                $validation = $sheet->getCell($cell)->getDataValidation();
                $validation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Error de entrada');
                $validation->setError('El valor no está en la lista.');
                //$validation->setPromptTitle('Elegir de la lista');
                //$validation->setPrompt('Por favor, elija un valor de la lista desplegable');
                $validation->setFormula1($info['formula']);
                $sheet->setDataValidation($info['range'], $validation);
            }
        })->download('xlsx');
    }
}
