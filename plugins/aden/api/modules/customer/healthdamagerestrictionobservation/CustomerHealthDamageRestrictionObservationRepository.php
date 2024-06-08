<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\HealthDamageRestrictionObservation;

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

class CustomerHealthDamageRestrictionObservationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerHealthDamageRestrictionObservationModel());

        $this->service = new CustomerHealthDamageRestrictionObservationService();
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



    // AllHealthDamageRestrictionObservation

    public function AllHealthDamageRestrictionObservation($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_health_damage_restriction_observation.id",
            "dateOf" => "wg_customer_health_damage_restriction_observation.dateOf",
            "name" => "users.name",
            "type" => "work_health_damage_restriction_observation_type.item AS type",
            "accessLevel" => "work_health_damage_restriction_observation_access.item AS accessLevel",
            "description" => "wg_customer_health_damage_restriction_observation.description",
            "customerHealthDamageRestrictionId" => "wg_customer_health_damage_restriction_observation.customer_health_damage_restriction_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();


        /* Example relation*/
        $query->join("wg_customer_health_damage_restriction", function ($join) {
            $join->on('wg_customer_health_damage_restriction_observation.customer_health_damage_restriction_id', '=', 'wg_customer_health_damage_restriction.id');

        })->join("users", function ($join) {
            $join->on('wg_customer_health_damage_restriction.createdBy', '=', 'users.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_restriction_observation_type','work_health_damage_restriction_observation_type')), function ($join) {
            $join->on('wg_customer_health_damage_restriction_observation.type', '=', 'work_health_damage_restriction_observation_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_restriction_observation_access','work_health_damage_restriction_observation_access')), function ($join) {
            $join->on('wg_customer_health_damage_restriction_observation.accessLevel', '=', 'work_health_damage_restriction_observation_access.value');
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

    // AllHealthDamageRestrictionObservationAll

    public function AllHealthDamageRestrictionObservationAll($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "createdAt" => "wg_customer_health_damage_restriction.created_at AS restrictionDate",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "dateOf" => "wg_customer_health_damage_restriction_observation.dateOf AS observationDate",
            "name" => "users.name",
            "restriccion" => "work_health_damage_restriction_observation_type.item AS restriccion",
            "accessLevel" => "work_health_damage_restriction_observation_access.item AS accessLevel",
            "description" => "wg_customer_health_damage_restriction_observation.description",
            "customerId" => "wg_customer_employee.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();


        /* Example relation*/
        $query->join("wg_customer_health_damage_restriction", function ($join) {
            $join->on('wg_customer_health_damage_restriction_observation.customer_health_damage_restriction_id', '=', 'wg_customer_health_damage_restriction.id');

        })->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_health_damage_restriction.customer_employee_id', '=', 'wg_customer_employee.id');

        })->join("wg_customers", function ($join) {
            $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');

        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

        })->join("users", function ($join) {
            $join->on('wg_customer_health_damage_restriction.createdBy', '=', 'users.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_restriction_observation_type','work_health_damage_restriction_observation_type')), function ($join) {
            $join->on('wg_customer_health_damage_restriction_observation.type', '=', 'work_health_damage_restriction_observation_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('work_health_damage_restriction_observation_access','work_health_damage_restriction_observation_access')), function ($join) {
            $join->on('wg_customer_health_damage_restriction_observation.accessLevel', '=', 'work_health_damage_restriction_observation_access.value');
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
