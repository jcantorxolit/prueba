<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigProcess;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CustomerRepository;
use DB;
use Excel;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigProcessRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigProcessModel());

        $this->service = new CustomerConfigProcessService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_process.id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "macroprocess" => "wg_customer_config_macro_process.name AS macroprocess",
            "name" => "wg_customer_config_process.name",
            "status" => "config_workplace_status.item AS status",
            "customerId" => "wg_customer_config_process.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_process.workplace_id');

        })->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_macro_process.id', '=', 'wg_customer_config_process.macro_process_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('config_workplace_status')), function ($join) {
            $join->on('config_workplace_status.value', '=', 'wg_customer_config_process.status');

        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->workplace_id = $entity->workplaceId;
        $entityModel->macro_process_id = $entity->macroProcessId ? $entity->macroProcessId->id : null;
        $entityModel->name = $entity->name;
        $entityModel->status = $entity->status;

        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;            
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }
        
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
            $entity->workplaceId = $model->workplaceId;
            $entity->macroProcessId = $model->macroProcessId;
            $entity->name = $model->name;
            $entity->status = $model->status;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/PlantillaProcesos.xlsx";

        $workplace = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, (new CustomerRepository)->getWorkplaceList($customerId));

        $macroprocess = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, $this->service->getMacroprocessList($customerId));  
        
        $workplace = CmsHelper::prependEmptyItemInArray($workplace);
        $macroprocess = CmsHelper::prependEmptyItemInArray($macroprocess);

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($workplace, $macroprocess) {
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

            $sheet->fromArray($workplace, null, 'A1', false);
            $sheet->fromArray($macroprocess, null, 'B1', false);

            $sheet = $file->setActiveSheetIndex(0);

            $cels = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'CentroTrabajo'],
                'B2' => ['range' => 'B2:B5000', 'formula' => 'Macroproceso'],
                'D2' => ['range' => 'D2:D5000', 'formula' => 'Estado'],
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
