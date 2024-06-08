<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Contractor;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;

class CustomerContractorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerContractorModel());

        $this->service = new CustomerContractorService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_contractor.id",
            "documentType" => "tipodoc.item AS documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "contract" => "wg_customer_contractor.contract",
            "status" => "estado.item As status",
            "isActive" => "wg_customer_contractor.isActive",
            "customerId" => "wg_customer_contractor.customer_id",
            "contractorId" => "wg_customer_contractor.contractor_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();


        $query->leftjoin("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_contractor.customer_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customer_contractor.isActive', '=', 'estado.value');

        });

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

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
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
        $entityModel->contractorId = $entity->contractorId ? $entity->contractorId->id : null;
        $entityModel->contractorTypeId = $entity->contractorTypeId ? $entity->contractorTypeId->id : null;
        $entityModel->contract = $entity->contract;
        $entityModel->isactive = $entity->isactive == 1;
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
            $entity->contractorId = $model->contractorId;
            $entity->contractorTypeId = $model->contractorTypeId;
            $entity->contract = $model->contract;
            $entity->isactive = $model->isactive;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    public function getList($criteria)
    {
        return $this->service->getList($criteria);
    }

    public function getCustomerContractorList($criteria)
    {
        return $this->service->getCustomerContractorList($criteria);
    }

    public function getCustomerRelationships($criteria)
    {
        return $this->service->getCustomerRelationships($criteria);
    }

    public function getCustomerRelationshipsGrid($criteria)
    {
        $this->setColumns([
            'documentNumber' => 'documentNumber',
            'businessName' => 'businessName',
            'relationship' => 'relationship',
            'status' => 'status',
            'parentId' => 'parentId'
        ]);

        $query = $this->service->getGridCustomerRelationships();

        $this->parseCriteria($criteria);
        $this->applyCriteria($query, $criteria);

        $this->addSortColumn('documentNumber');

        $query = $this->query($query);
        return $this->get($query, $criteria);
    }
}
