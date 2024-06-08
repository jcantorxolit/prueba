<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ArlServiceCost;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use Exception;
use Excel;
use Carbon\Carbon;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class CustomerArlServiceCostRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerArlServiceCostModel());
        $this->service = new CustomerArlServiceCostService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_arl_service_cost.id",
            "registrationDate" => DB::raw("DATE(wg_customer_arl_service_cost.registration_date) AS registration_date"),
            "service" => "customer_arl_service.item AS service",
            "cost" => "wg_customer_arl_service_cost.cost",
            "year" => DB::raw("YEAR(wg_customer_arl_service_cost.registration_date) AS year"),
            "customerId" => "wg_customer_arl_service_cost.customer_id"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_arl_service')), function ($join) {
                $join->on('customer_arl_service.value', '=', 'wg_customer_arl_service_cost.service');
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
        $entityModel->service = $entity->service ? $entity->service->value : null;
        $entityModel->cost = $entity->cost;

        if ($isNewRecord) {
            $entityModel->registrationDate = Carbon::now()->timezone('America/Bogota');
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

    public function parseModelWithRelations(CustomerArlServiceCostModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->registrationDate = Carbon::parse($model->registrationDate);
            $entity->service = $model->getService();
            $entity->cost = $model->cost;
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

    public function getAllYears($customerId)
    {
        return $this->service->getAllYears($customerId);
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/plantilla_importacion_manillas.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
    }
}
