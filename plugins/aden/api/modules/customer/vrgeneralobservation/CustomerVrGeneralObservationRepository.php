<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\VrGeneralObservation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Modules\Customer\Parameter\CustomerParameterRepository;
use Exception;
use Excel;
use Carbon\Carbon;
use DB;

class CustomerVrGeneralObservationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerVrGeneralObservationModel());
        $this->service = new CustomerVrGeneralObservationService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_general_observation.id",
            "registrationDate" => DB::raw("DATE(wg_customer_vr_general_observation.registration_date) AS registration_date"),
            "observation" => "wg_customer_vr_general_observation.observation",
            "createdByUser" => DB::raw("CONCAT(users.name, ' ', users.surname) AS createdByUser"),
            "customerId" => "wg_customer_vr_general_observation.customer_id"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_vr_general_observation.created_by');
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
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
        $entityModel->customerId = $entity->customerId;
        $entityModel->registrationDate = Carbon::parse($entity->registrationDate)->timezone('America/Bogota');
        $entityModel->observation = $entity->observation;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if ($entity->isCustomObservation) {
            CustomerParameterRepository::createCustoomerVrObservation($entityModel->customerId, $entityModel->observation);
        }

        return $entityModel;
    }

    public function parseModelWithRelations(CustomerVrGeneralObservationModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->registrationDate = Carbon::parse($model->registrationDate);
            $entity->observation = $model->observation;
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

        $entityModel->delete();
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/plantilla_importacion_manillas.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
    }
}
