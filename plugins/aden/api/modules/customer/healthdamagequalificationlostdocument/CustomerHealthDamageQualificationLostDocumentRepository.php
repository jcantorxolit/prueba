<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\HealthDamageQualificationLostDocument;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\Customer\CustomerModel;
use Wgroup\SystemParameter\SystemParameter;

class CustomerHealthDamageQualificationLostDocumentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerHealthDamageQualificationLostDocumentModel());

        $this->service = new CustomerHealthDamageQualificationLostDocumentService();
    }

    public static function getCustomFilters()
    {
        return [];
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
            "id" => "wg_customer_contract_detail_comment.id",
            "comment" => "wg_customer_contract_detail_comment.comment",
            "user" => "users.name AS user",
            "createdAt" => "wg_customer_contract_detail_comment.created_at",
            "customerContractDetailId" => "wg_customer_contract_detail_comment.customer_contract_detail_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join('wg_customer_contract_detail', function ($join) {
            $join->on('wg_customer_contract_detail.id', '=', 'wg_customer_contract_detail_comment.customer_contract_detail_id');

        })->leftjoin('users', function ($join) {
            $join->on('users.id', '=', 'wg_customer_contract_detail_comment.created_by');

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

        $entityModel->customerContractDetailId = $entity->customerContractDetailId;
        $entityModel->comment = $entity->comment;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
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

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }



    // AllHealthDamageQualificationLostDocument

    public function AllHealthDamageQualificationLostDocument($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_health_damage_ql_document.id",
            "item" => "work_health_damage_ql_document_type.item",
            "name" => "wg_customer_health_damage_ql_document.name",
            "description" => "wg_customer_health_damage_ql_document.description",
            "version" => "wg_customer_health_damage_ql_document.version",
            "status" => "wg_customer_health_damage_ql_document.status",
            "customerHealthDamageQualificationLostId" => "wg_customer_health_damage_ql_document.customer_health_damage_qualification_lost_id",
            "entityCode" => "wg_customer_health_damage_ql_document.entityCode",
            "entityId" => "wg_customer_health_damage_ql_document.entityId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();


        /* Example relation*/
        $query->join("wg_customer_health_damage_ql", function ($join) {
            $join->on('wg_customer_health_damage_ql_document.customer_health_damage_qualification_lost_id', '=', 'wg_customer_health_damage_ql.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_ql_document_type','work_health_damage_ql_document_type')), function ($join) {
            $join->on('wg_customer_health_damage_ql_document.type', '=', 'work_health_damage_ql_document_type.value');
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
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
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
}
