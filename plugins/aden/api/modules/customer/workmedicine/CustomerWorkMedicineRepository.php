<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\WorkMedicine;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use Carbon\Carbon;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class CustomerWorkMedicineRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerWorkMedicineModel());

        $this->service = new CustomerWorkMedicineService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_work_medicine.id",
            "examinationDate" => "wg_customer_work_medicine.examinationDate",
            "examinationType" => "work_medicine_examination_type.item AS examinationType",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "job" => "wg_customer_config_job_data.name as job",
            "customerId" => "wg_customer_employee.customer_id",
            "customerEmployeeId" => "wg_customer_work_medicine.customer_employee_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_work_medicine.customer_employee_id');

        })->join("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_medicine_medical_concept')), function ($join) {
            $join->on('work_medicine_medical_concept.value', '=', 'wg_customer_work_medicine.medicalConcept');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_medicine_examination_type')), function ($join) {
            $join->on('work_medicine_examination_type.value', '=', 'wg_customer_work_medicine.examinationType');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('employee_contract_type.value', '=', 'wg_customer_employee.contractType');

        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');

        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');

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

        $entityModel->customerEmployeeId = $entity->customerEmployeeId;
        $entityModel->examinationtype = $entity->examinationtype ? $entity->examinationtype->value : null;
        $entityModel->examinationdate = $entity->examinationdate ? Carbon::parse($entity->examinationdate)->timezone('America/Bogota') : null;
        $entityModel->occupationalconclusion = $entity->occupationalconclusion;
        $entityModel->occupationalbehavior = $entity->occupationalbehavior;
        $entityModel->generalrecommendation = $entity->generalrecommendation;
        $entityModel->medicalconcept = $entity->medicalconcept ? $entity->medicalconcept->value : null;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;

        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
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
            $entity->customerEmployeeId = $model->customerEmployeeId;
            $entity->examinationtype = $model->getExaminationtype();
            $entity->examinationdate = $model->examinationdate;
            $entity->occupationalconclusion = $model->occupationalconclusion;
            $entity->occupationalbehavior = $model->occupationalbehavior;
            $entity->generalrecommendation = $model->generalrecommendation;
            $entity->medicalconcept = $model->getMedicalconcept();
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;

            return $entity;
        } else {
            return null;
        }
    }

    //Función que genera la data del excel
    public function getInfoExportExcel($criteria)
    {
        $header = [
            "NOMBRE" => "firstName",
            "APELLIDOS" => "lastName",
            "FECHA DEL EXAMEN" => "examinationDate",
            "TIPO DE EXAMEN" => "examinationType",
            "CONCLUSIONES OCUPACIONALES" => "conclusiones",
            "CONDUCTAS OCUPACIONALES PREVER" => "conductas",
            "RECOMENDACIONES GENERALES" => "recomendaciones",
            "CONCEPTO MEDICO DE APTITUP OCUPACIONAL" => "medicalConcept",
            "CARGO" => "job",
            "PRUEBA COMPLEMENTARIA" => "complementatyTest",
            "RESULTADO PRUEBA COMPLEMENTARIA" => "result",
            "INTERPRETACION" => "interpretation"
        ];

        $query = $this->service->getInfoExportExcel($criteria);

        $data = ExportHelper::headings($query->get()->toArray(), $header);

        $filename = 'Examenes Ocupacionales' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Actividades', $data);
    }

    public function getTemplateFile()
    {
        $instance = CmsHelper::getInstance();
        $filePath = "templates/$instance/Plantilla_Creación_Examen_Ocupacional.xlsx";
        return response()->download(CmsHelper::getStorageTemplateDir($filePath));
    }
}
