<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Manacle;

use AdeN\Api\Classes\AuthClient;
use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\ManacleEmployee\CustomerManacleEmployeeModel;
use AdeN\Api\Helpers\CmsHelper;
use Exception;
use Log;
use Excel;
use Carbon\Carbon;
use DB;

class CustomerManacleRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerManacleModel());
        $this->service = new CustomerManacleService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "id",
            "registrationDate" => "registration_date",
            "number" => "number",
            "isActive" => DB::raw("IF(is_active=1,'Activo','Inactivo') as isActive"),
            "customerId" => "customer_id"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        $entityToCompare = $this->model
        ->where('number', $entity->number)
        ->where('customer_id', $entity->customerId)
        ->first();
        
        if ((!is_null($entityToCompare) && $entity->id == 0)) {
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

        $entityModel->id = $entity->id;
        $entityModel->registrationDate = Carbon::parse($entity->registrationDate)->timezone('America/Bogota');
        $entityModel->number = $entity->number;
        $entityModel->isActive = $entity->isActive->value;
        $entityModel->customerId = $entity->customerId;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $this->attachInstance($entityModel->number);

        return $entityModel;
    }

    private function attachInstance($deviceId)
    {
        try {
            AuthClient::getInstance()->post('v1/customer-manacle/attach-instance', [
                "deviceId" => $deviceId
            ]);
        } catch (\Exception $ex) {
            \Log::error($ex);
        }
    }

    private function detachInstance($deviceId)
    {
        try {
            AuthClient::getInstance()->post('v1/customer-manacle/detach-instance', [
                "deviceId" => $deviceId
            ]);
        } catch (\Exception $ex) {
            \Log::error($ex);
        }
    }

    public function parseModelWithRelations(CustomerManacleModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->registrationDate = Carbon::parse($model->registrationDate);
            $entity->number = $model->number;
            $entity->isActive = $model->isActive ? (object)["item" => "Activo", "value" => 1] : (object)["item" => "Inactivo", "value" => 0];
            $entity->customerId = $model->customerId;
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

        if (CustomerManacleEmployeeModel::whereManacleId($id)->first()) {
            throw new Exception("No se puede eliminar el registro, esta manilla se encuentra asociada a un empleado.");
        }

        $entityModel->delete();

        $this->detachInstance($entityModel->number);
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/plantilla_importacion_manillas.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
    }


}