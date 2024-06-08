<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Employee;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Session;
use Excel;

use Wgroup\SystemParameter\SystemParameter;
use Wgroup\Employee\EmployeeDTO;

use AdeN\Api\Modules\EmployeeInformationDetail\EmployeeInformationDetailRepository;
use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Modules\Customer\Employee\Audit\CustomerEmployeeAuditModel;
use Illuminate\Database\Eloquent\Collection;

class CustomerEmployeeRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerEmployeeModel());

        $this->service = new CustomerEmployeeService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Número de Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "firstName"],
            ["alias" => "Apellidos", "name" => "lastName"],
            ["alias" => "Centro de Trabajo", "name" => "workPlace"],
            ["alias" => "Cargo", "name" => "job"],
            ["alias" => "Centro de Costos", "name" => "neighborhood"],
            ["alias" => "Anexos", "name" => "countAttachment"],
            ["alias" => "Estado", "name" => "isActive"],
            ["alias" => "Autorización", "name" => "isAuthorized"],
        ];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_employee.id",
            "documentNumber" => "wg_employee.documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "workPlace" => "wg_customer_config_workplace.name as workPlace",
            "job" => "wg_customer_config_job_data.name as job",
            "neighborhood" => "wg_employee.neighborhood",
            "countAttachment" => DB::raw("IFNULL(employee_document_stats.qryAttachment, 0) AS countAttachment"),
            "isActiveCode" => DB::raw("CASE WHEN wg_customer_employee.isActive = 1 THEN 'Activo' ELSE 'Inactivo' END AS isActiveCode"),
            "isAuthorized" => DB::raw("CASE WHEN wg_customer_employee.isAuthorized = 1 THEN 'Autorizado' WHEN  wg_customer_employee.isAuthorized = 0 THEN 'No Autorizado' ELSE 'N/A' END AS isAuthorized"),
            "employeeDocumentType" => "employee_document_type.item as employeeDocumentType",
            "customerId" => "wg_customer_employee.customer_id",
            "employeeId" => "wg_customer_employee.employee_id",
            "isActive" => DB::raw("CASE WHEN wg_customer_employee.isActive = 1 THEN 'Activo' ELSE 'Inactivo' END AS isActive"),
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customers", function ($join) {
            $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_employee.job', '=', 'wg_customer_config_job.id');
        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
        })->leftjoin(DB::raw(CustomerEmployeeModel::getRelationDocumentCount('employee_document_stats')), function ($join) {
            $join->on('wg_customer_employee.id', '=', 'employee_document_stats.customer_employee_id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allLess($criteria)
    {
        $documents = CriteriaHelper::getMandatoryFilter($criteria, 'documentNumber');
        $criteria->pageSize = 0;
        $result = $this->all($criteria);
        $all = collect($result["data"]);



        collect($documents->value)->filter(function ($document) use ($all) {
            return !$all->contains("documentNumber", $document);
        })->each(function (&$document) use (&$all) {
            $all->push((object)[
                "id" => null,
                "documentNumber" => $document,
                "firstName" => null,
                "lastName" => null,
                "workPlace" => null,
                "job" => null,
                "neighborhood" => null,
                "countAttachment" => null,
                "isActiveCode" => null,
                "isAuthorized" => null,
                "employeeDocumentType" => null,
                "customerId" => null,
                "employeeId" => null,
                "isActive" => null
            ]);
        });

        $result["data"] = $all->unique()->values();
        return $result;
    }

    public function allModalBasic($criteria, $jobConditions)
    {
        $this->setColumns([
            "id" => "wg_customer_employee.id",
            "documentNumber" => "wg_employee.documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "workPlace" => "wg_customer_config_workplace.name as workPlace",
            "job" => "wg_customer_config_job_data.name as job",
            "neighborhood" => "wg_employee.neighborhood",
            "isActiveCode" => DB::raw("CASE WHEN wg_customer_employee.isActive = 1 THEN 'Activo' ELSE 'Inactivo' END AS isActiveCode"),
            "isAuthorized" => DB::raw("CASE WHEN wg_customer_employee.isAuthorized = 1 THEN 'Autorizado' WHEN  wg_customer_employee.isAuthorized = 0 THEN 'No Autorizado' ELSE 'N/A' END AS isAuthorized"),
            "customerId" => "wg_customer_employee.customer_id",
            "isActive" => "wg_customer_employee.isActive"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customers", function ($join) {
            $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_employee.job', '=', 'wg_customer_config_job.id');
        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
        });

        /*Validación empleados que tienen una autoevaluación completada*/
        if ($jobConditions) {
            $query->join('wg_customer_job_condition as condi', 'condi.customer_employee_id', '=', 'wg_customer_employee.id')
                ->join('wg_customer_job_condition_self_evaluation as eval', 'eval.job_condition_id', '=', 'condi.id')
                ->where('eval.fully_answered', 1)
                ->groupby('wg_customer_employee.id');
        }

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allModalBasic2($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_employee.id",
            "employeeDocumentType" => "employee_document_type.item as employeeDocumentType",
            "documentNumber" => "wg_employee.documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "customerId" => "wg_customer_employee.customer_id",
            "isActive" => "wg_customer_employee.isActive"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customers", function ($join) {
            $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
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
        $entityModel->employee_id = $entity->employeeId;
        $entityModel->contracttype = $entity->contracttype;
        $entityModel->occupation = $entity->occupation;
        $entityModel->job = $entity->job;
        $entityModel->workplace = $entity->workplace;
        $entityModel->salary = $entity->salary;
        $entityModel->type = $entity->type;
        $entityModel->isactive = $entity->isactive == 1;
        $entityModel->isauthorized = $entity->isauthorized == 1;


        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public static function createFromSignUp($entity)
    {
        $entityUser = $entity->user;
        $entityUser->id = isset($entityUser->id) ? $entityUser->id : 0;
        $employee = EmployeeDTO::fillAndQuickSaveModel($entityUser);

        $newEntity = new \stdClass();
        $newEntity->id = 0;
        $newEntity->customerId = $entity->customer->id;
        $newEntity->employeeId = $employee->id;
        $newEntity->contracttype = null;
        $newEntity->occupation = null;
        $newEntity->job = null;
        $newEntity->workplace = null;
        $newEntity->salary = null;
        $newEntity->type = null;
        $newEntity->isactive = true;
        $newEntity->isauthorized = false;

        $entitModel = (new self)->insertOrUpdate($newEntity);

        $entitModel->email = CmsHelper::parseToStdClass([
            "id" => 0,
            "value" => $entityUser->email
        ]);
        $entitModel->mobile = null;

        (new self)->insertOrUpdateInfoDetail($entitModel);

        return $entitModel;
    }

    public function insertOrUpdateInfoDetail($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            return null;
        }

        $infoDetailRepository = new EmployeeInformationDetailRepository();
        $primaryEmail = $entity->email ? $infoDetailRepository->insertOrUpdate($entity->employeeId, self::ENTITY_NAME, "email", $entity->email) : null;
        $primaryCellphone = $entity->mobile ? $infoDetailRepository->insertOrUpdate($entity->employeeId, self::ENTITY_NAME, "cel", $entity->mobile) : null;

        $entityModel->primary_email = $primaryEmail ? $primaryEmail->id : null;
        $entityModel->primary_cellphone = $primaryCellphone ? $primaryCellphone->id : null;
        $entityModel->save();

        return $this->parseModelWithRelations($entityModel);
    }

    public function updateAuthStatus($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            return null;
        }

        if ($entityModel != null) {
            if ($entityModel->isAuthorized != $entity->isAuthorized) {    
                $authUser = $this->getAuthUser();

                $description = $entity->isAuthorized ? "autorización" : "desautorización";            
                $action = $entity->isAuthorized ? "Autorizado" : "Desautorizado";

                $customerEmployeeAudit = new CustomerEmployeeAuditModel();
                $customerEmployeeAudit->customer_employee_id = $entityModel->id;
                $customerEmployeeAudit->model_name = "Empleados";
                $customerEmployeeAudit->model_id = $entityModel->id;
                $customerEmployeeAudit->user_type = $authUser->wg_type;
                $customerEmployeeAudit->user_id = $authUser->id;
                $customerEmployeeAudit->action = "{$action} Manual";
                $customerEmployeeAudit->observation = !empty($entity->reason) ? $entity->reason : "";
                $customerEmployeeAudit->date = Carbon::now('America/Bogota');
                $customerEmployeeAudit->save();

                $entityModel->isAuthorized = $entity->isAuthorized;
                $entityModel->save();
            }
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customer_id;
            $entity->employeeId = $model->employee_id;
            $entity->employee = $model->getEmployeeEntity();
            $entity->mobile = $model->getInfoDetailBy($model->primary_cellphone);
            $entity->email = $model->getInfoDetailBy($model->primary_email);

            return $entity;
        } else {
            return null;
        }
    }

    public function findInCustomer($criteria)
    {
        return $this->service->findInCustomer($criteria);
    }

    public function findInDifferentCustomer($criteria)
    {
        return $this->service->findInDifferentCustomer($criteria);
    }

    public function findByDocument($criteria)
    {
        return $this->service->findByDocument($criteria);
    }

    public function backupRecovery($entity)
    {
        $sessionId = Session::getId();
        $authUser = $this->getAuthUser();
        $registerDate = Carbon::now('America/Bogota')->format('Y-m-d H:i:s');
        $items = (new Collection($entity->items))->filter(function ($item) {
            return $item->isActive;
        })->map(function ($item) {
            return $item->value;
        })->toArray();

        $entities = implode(',', $items);

        DB::statement("CALL TL_Employee_Backup_Recovery(
            {$entity->customerId},
            '$sessionId',
            '$entities',
            {$authUser->id},
            '$registerDate'
        )");

        return $registerDate;
    }

    public function downloadDocument()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_Employee_Import_Document.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
    }

    public function processLessImport($file)
    {
        $filters = [];
        Excel::load($file, function ($doc) use (&$filters) {
            $sheet = $doc->get(["documento_empleado"]);
            foreach ($sheet as $document) {
                $filters[] = $document->documento_empleado;
            }
        });

        return $filters;
    }
}
