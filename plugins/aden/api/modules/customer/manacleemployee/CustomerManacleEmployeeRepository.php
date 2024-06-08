<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ManacleEmployee;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\Covid\DailyPersonNear\CustomerCovidDailyPersonNearModel;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use AdeN\Api\Modules\Customer\Manacle\CustomerManacleModel;
use Wgroup\SystemParameter\SystemParameter;
use System\Models\Parameters;
use AdeN\Api\Helpers\CmsHelper;
use Excel;
use Exception;
use Log;
use Carbon\Carbon;
use DB;

class CustomerManacleEmployeeRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerManacleEmployeeModel());
        $this->service = new CustomerManacleEmployeeService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_manacle_employee.id",
            "registrationDate" => "wg_customer_manacle_employee.registration_date",
            "manacleNumber" => "wg_customer_manacle.number AS manacleNumber",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber AS documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "isActive" => DB::raw("IF(wg_customer_manacle_employee.is_active=1,'Activo','Inactivo') as isActive"),
            "customerId" => "wg_customer_manacle_employee.customer_id"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_manacle_employee.customer_employee_id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
        })->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('employee_document_type.value', '=', 'wg_customer_manacle_employee.document_type');
        })->join("wg_customer_manacle", function ($join) {
            $join->on('wg_customer_manacle_employee.manacle_id', '=', 'wg_customer_manacle.id');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        $entityToCompare = $this->model
        ->where('manacle_id', $entity->manacleId)
        ->where('customer_id', $entity->customerId)
        ->first();
        
        if ((!is_null($entityToCompare) && $entity->id == 0) || (!is_null($entityToCompare) && $entity->id != $entityToCompare->id)) {
            return false;
        }

        return true;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;
        $authUser = $this->getAuthUser();
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $documentType = Parameters::whereNamespace("wgroup")->whereGroup("employee_document_type")->whereItem($entity->documentType)->first();
        $entityModel->id = $entity->id;
        $entityModel->registrationDate = Carbon::parse($entity->registrationDate)->timezone('America/Bogota');
        $entityModel->manacleId = $entity->manacleId;
        $entityModel->isActive = $entity->isActive;
        $entityModel->customerId = $entity->customerId;
        $entityModel->customerEmployeeId = $entity->customerEmployeeId;
        $entityModel->documentType = $documentType ? $documentType->attributes["value"] : null;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function parseModelWithRelations(CustomerManacleEmployeeModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {

            $manacleRepo = CustomerManacleModel::find($model->manacleId);
            $employee = (new CustomerEmployeeDTO())->find($model->customerEmployeeId, 2);
            $documentType = Parameters::whereNamespace("wgroup")->whereGroup("employee_document_type")->whereValue($model->documentType)->first();

            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->registrationDate = Carbon::parse($model->registrationDate);
            $entity->manacleId = $model->manacleId;
            $entity->manacleNumber = $manacleRepo->number;
            $entity->isActive = $model->isActive == 1;
            $entity->customerId = $model->customerId;
            $entity->customerEmployeeId = $model->customerEmployeeId;
            $entity->documentType = $documentType ? $documentType->item : null;
            $entity->documentNumber = $employee->entity->documentNumber;
            $entity->firstName = $employee->entity->firstName;
            $entity->lastName = $employee->entity->lastName;
            return $entity;
        } else {
            return null;
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        if (CustomerCovidDailyPersonNearModel::whereManacleEmployeeId($entityModel->manacleId)->first()) {
            throw new Exception("No se puede eliminar el registro, ya se encuentra asociado en un registro de covid.");
        }
        

        $entityModel->delete();
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/plantilla_importacion_manillas_empleados.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
    }

}