<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InternalProject;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Carbon\Carbon;
use Exception;

class CustomerInternalProjectRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerInternalProjectModel());

        $this->service = new CustomerInternalProjectService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_internal_project.id",
            "customerId" => "wg_customer_internal_project.customer_id",
            "name" => "wg_customer_internal_project.name",
            "type" => "wg_customer_internal_project.type",
            "description" => "wg_customer_internal_project.description",
            "serviceorder" => "wg_customer_internal_project.serviceOrder",
            "defaultskill" => "wg_customer_internal_project.defaultSkill",
            "estimatedhours" => "wg_customer_internal_project.estimatedHours",
            "deliverydate" => "wg_customer_internal_project.deliveryDate",
            "isrecurrent" => "wg_customer_internal_project.isRecurrent",
            "status" => "wg_customer_internal_project.status",
            "isbilled" => "wg_customer_internal_project.isBilled",
            "invoicenumber" => "wg_customer_internal_project.invoiceNumber",
            "previousId" => "wg_customer_internal_project.previous_id",
            "createdby" => "wg_customer_internal_project.createdBy",
            "updatedby" => "wg_customer_internal_project.updatedBy",
            "createdAt" => "wg_customer_internal_project.created_at",
            "updatedAt" => "wg_customer_internal_project.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
        $query->leftjoin("tableParent", function ($join) {
        $join->on('wg_customer_internal_project.parent_id', '=', 'tableParent.id');
        }
         */

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId ? $entity->customerId->id : null;
        $entityModel->name = $entity->name;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->description = $entity->description;
        $entityModel->serviceorder = $entity->serviceorder;
        $entityModel->defaultskill = $entity->defaultskill;
        $entityModel->estimatedhours = $entity->estimatedhours;
        $entityModel->deliverydate = $entity->deliverydate ? Carbon::parse($entity->deliverydate)->timezone('America/Bogota') : null;
        $entityModel->isrecurrent = $entity->isrecurrent == 1;
        $entityModel->status = $entity->status;
        $entityModel->isbilled = $entity->isbilled == 1;
        $entityModel->invoicenumber = $entity->invoicenumber ? $entity->invoicenumber->value : null;
        $entityModel->previousId = $entity->previousId ? $entity->previousId->id : null;
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
            $entity->customerId = $model->customerId;
            $entity->name = $model->name;
            $entity->type = $model->getType();
            $entity->description = $model->description;
            $entity->serviceorder = $model->serviceorder;
            $entity->defaultskill = $model->defaultskill;
            $entity->estimatedhours = $model->estimatedhours;
            $entity->deliverydate = $model->deliverydate;
            $entity->isrecurrent = $model->isrecurrent;
            $entity->status = $model->status;
            $entity->isbilled = $model->isbilled;
            $entity->invoicenumber = $model->getInvoicenumber();
            $entity->previousId = $model->previousId;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;

            return $entity;
        } else {
            return null;
        }
    }

    public function allYears()
    {
        return $this->service->allYears();
    }
}
