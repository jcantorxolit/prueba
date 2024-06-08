<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigMacroProcess;

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

class CustomerConfigMacroProcessRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigMacroProcessModel());

        $this->service = new CustomerConfigMacroProcessService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_macro_process.id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "name" => "wg_customer_config_macro_process.name",
            "status" => "config_workplace_status.item AS status",
            "customerId" => "wg_customer_config_macro_process.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();


        $query->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_macro_process.workplace_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('config_workplace_status')), function ($join) {
            $join->on('config_workplace_status.value', '=', 'wg_customer_config_macro_process.status');
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

    public static function createGeneral($parent)
    {
        $newEntity = new \stdClass();

        $newEntity->id = 0;
        $newEntity->name = 'GENERAL';
        $newEntity->customerId = $parent->customerId;
        $newEntity->workplaceId = $parent->id;
        $newEntity->status = "Activo";

        return (new self)->insertOrUpdate($newEntity);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }
        
        return $entityModel->delete();        
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
        $file = "templates/$instance/PlantillaMacroProcesos.xlsx";

        $data = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, (new CustomerRepository)->getWorkplaceList($customerId));

        $cells = CmsHelper::prependEmptyItemInArray($data);
        
        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($cells) {
            $sheet = $file->setActiveSheetIndex(1);

            $file->addNamedRange(
                new \PHPExcel_NamedRange(
                    'CentroTrabajo', 
                    $file->getActiveSheet(), 
                    'A1:A'. count($cells)
                )
            );

            $sheet->fromArray($cells, null, 'A1', false);

            $sheet = $file->setActiveSheetIndex(0);

            $cels = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'CentroTrabajo'],
                'C2' => ['range' => 'C2:C5000', 'formula' => 'Estado'],
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
