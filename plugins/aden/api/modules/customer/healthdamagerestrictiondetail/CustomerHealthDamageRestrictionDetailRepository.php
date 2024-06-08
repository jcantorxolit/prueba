<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\HealthDamageRestrictionDetail;

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

class CustomerHealthDamageRestrictionDetailRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerHealthDamageRestrictionDetailModel());

        $this->service = new CustomerHealthDamageRestrictionDetailService();
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



    // AllHealthDamageRestrictionDetail

    public function AllHealthDamageRestrictionDetail($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_health_damage_restriction_detail.id",
            "dateOfIssue" => "wg_customer_health_damage_restriction_detail.dateOfIssue",
            "expirationDate" => "wg_customer_health_damage_restriction_detail.expirationDate",
            "item" => "work_health_damage_restriction.item",
            "description" => "wg_customer_health_damage_restriction_detail.description",
            "isPermanentManagement" => "wg_customer_health_damage_restriction_detail.isPermanentManagement",
            "customerHealthDamageRestrictionId" => "wg_customer_health_damage_restriction_detail.customer_health_damage_restriction_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();


        /* Example relation*/
        // INNER JOIN `wg_customer_health_damage_restriction` ON `wg_customer_health_damage_restriction_detail`.`customer_health_damage_restriction_id` = `wg_customer_health_damage_restriction`.`id`
        $query->join("wg_customer_health_damage_restriction", function ($join) {
            $join->on('wg_customer_health_damage_restriction_detail.customer_health_damage_restriction_id', '=', 'wg_customer_health_damage_restriction.id');

        //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.GROUP = 'work_health_damage_restriction') work_health_damage_restriction
            //ON `wg_customer_health_damage_restriction_detail`.`restriction` = `work_health_damage_restriction`.`value`
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_restriction','work_health_damage_restriction')), function ($join) {
            $join->on('wg_customer_health_damage_restriction_detail.restriction', '=', 'work_health_damage_restriction.value');

        //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.GROUP = 'work_health_damage_who_perceived') work_health_damage_who_perceived
            //ON `wg_customer_health_damage_restriction_detail`.`whoPerceived` = `work_health_damage_restriction`.`value`
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_who_perceived','work_health_damage_who_perceived')), function ($join) {
            $join->on('wg_customer_health_damage_restriction_detail.whoPerceived', '=', 'work_health_damage_who_perceived.value');
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
