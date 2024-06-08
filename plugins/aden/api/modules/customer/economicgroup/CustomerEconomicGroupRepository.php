<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\EconomicGroup;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Carbon\Carbon;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class CustomerEconomicGroupRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerEconomicGroupModel());

        $this->service = new CustomerEconomicGroupService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_economic_group.id",
            "parentId" => "wg_customer_economic_group.parent_id",
            "customerId" => "wg_customer_economic_group.customer_id",
            "isactive" => "wg_customer_economic_group.isActive",
            "createdby" => "wg_customer_economic_group.createdBy",
            "updatedby" => "wg_customer_economic_group.updatedBy",
            "createdAt" => "wg_customer_economic_group.created_at",
            "updatedAt" => "wg_customer_economic_group.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
        $query->leftjoin("tableParent", function ($join) {
        $join->on('wg_customer_economic_group.parent_id', '=', 'tableParent.id');
        }
         */

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allEconomigGroup($criteria)
    {
        $this->setColumns([
            "id" => "wg_customers.id",
            "documentType" => "tipodoc.item as documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "type" => "tipocliente.item AS type",
            "classification" => "customer_classification.item AS classification",
            "status" => "estado.item AS status",
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customers')
            ->select(
                'id', 'documentType', 'documentNumber', 'businessName', 'type', 'classification', 'status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.customer_id');

            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentType',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                'wg_customers.type',
                'wg_customers.classification',
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_economic_group.parent_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $q1->union($q2);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customers")))
            ->mergeBindings($q1);

        /* Example relation*/
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');

        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field != 'customerId') {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        return $this->get($query, $criteria);
    }

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customers.id",
            "documentType" => "tipodoc.item as documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "type" => "tipocliente.item AS type",
            "classification" => "customer_classification.item AS classification",
            "status" => "estado.item AS status",
            "customerId" => "wg_customers.id AS customerId",
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_economic_group')
            ->select(
                'id', 'parent_id', 'customer_id', 'isActive'
            );

        $query = $this->query(DB::table('wg_customers'))
            ->mergeBindings($q1);

        /* Example relation*/
        $query->leftjoin(DB::raw("({$q1->ToSql()}) AS wg_customer_economic_group"), function ($join) {
            $join->on('wg_customer_economic_group.customer_id', '=', 'wg_customers.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');

        })
        ->whereNull("wg_customer_economic_group.customer_id")
        ->whereRaw("(wg_customers.isDeleted = 0 OR wg_customers.isDeleted IS NULL)");

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
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

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

        $entityModel->parentId = $entity->parentId ? $entity->parentId->id : null;
        $entityModel->customerId = $entity->customerId ? $entity->customerId->id : null;
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
            $entity->parentId = $model->parentId;
            $entity->customerId = $model->customerId;
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
}
