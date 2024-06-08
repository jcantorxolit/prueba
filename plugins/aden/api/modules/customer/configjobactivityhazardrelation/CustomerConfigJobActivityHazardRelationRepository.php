<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigActivityHazard\CustomerConfigActivityHazardModel;
use Carbon\Carbon;
use DB;
use Exception;

class CustomerConfigJobActivityHazardRelationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigJobActivityHazardRelationModel());

        $this->service = new CustomerConfigJobActivityHazardRelationService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_hazard_relation.id",
            "classification" => "wg_config_job_activity_hazard_classification.name as classification",
            "type" => "wg_config_job_activity_hazard_type.name as type",
            "description" => "wg_config_job_activity_hazard_description.name as description",
            "effect" => "wg_config_job_activity_hazard_effect.name as effect",
            "timeExposure" => "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",

            "controlMethodSourceText" => "wg_customer_config_job_activity_hazard.control_method_source_text AS controlMethodSourceText",
            "controlMethodMediumText" => "wg_customer_config_job_activity_hazard.control_method_medium_text AS controlMethodMediumText",
            "controlMethodPersonText" => "wg_customer_config_job_activity_hazard.control_method_person_text AS controlMethodPersonText",
            "controlMethodAdministrativeText" => "wg_customer_config_job_activity_hazard.control_method_administrative_text AS controlMethodAdministrativeText",

            "measureND" => "measure_nd.name AS measureND",
            "measureNE" => "measure_ne.name AS measureNE",
            "measureNC" => "measure_nc.name AS measureNC",

            "riskValue" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue"),

            "customerConfigJobActivityId" => "wg_customer_config_job_activity_hazard_relation.customer_config_job_activity_id",
            "customerConfigJobActivityHazardId" => "wg_customer_config_job_activity_hazard_relation.customer_config_job_activity_hazard_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation */
        $query->join("wg_customer_config_job_activity_hazard", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_relation.customer_config_job_activity_hazard_id', '=', 'wg_customer_config_job_activity_hazard.id');

        })->join("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');

        })->join("wg_config_job_activity_hazard_description", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');

        })->join("wg_config_job_activity_hazard_effect", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');

        })->join("wg_config_job_activity_hazard_type", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');

        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');

        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');

        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');

        })->join("wg_customer_config_job_activity", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_relation.customer_config_job_activity_id', '=', 'wg_customer_config_job_activity.id');

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

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_hazard.id",
            "classification" => "wg_config_job_activity_hazard_classification.name as classification",
            "type" => "wg_config_job_activity_hazard_type.name as type",
            "description" => "wg_config_job_activity_hazard_description.name as description",
            "effect" => "wg_config_job_activity_hazard_effect.name as effect",
            "timeExposure" => "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",

            "measureND" => "measure_nd.name AS measureND",
            "measureNE" => "measure_ne.name AS measureNE",
            "measureNC" => "measure_nc.name AS measureNC",

            "riskValue" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue"),

            "jobActivityId" => "wg_customer_config_job_activity_hazard.job_activity_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customer_config_job_activity_hazard'));

        /* Example relation */
        $query->join("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');

        })->join("wg_config_job_activity_hazard_description", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');

        })->join("wg_config_job_activity_hazard_effect", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');

        })->join("wg_config_job_activity_hazard_type", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');

        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');

        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');

        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');

        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "notInRaw") {
                        $query->whereNotIn('wg_customer_config_job_activity_hazard.id', function ($query) use ($item) {
                            $query->select(DB::raw('customer_config_job_activity_hazard_id'))
                                ->from('wg_customer_config_job_activity_hazard_relation')
                                ->where('customer_config_job_activity_id', '=', SqlHelper::getPreparedData($item));
                        });
                    } else if ($item->operator == "raw") {
                        $query->where(function ($query) use ($item) {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), '=', SqlHelper::getPreparedData($item))
                                ->orWhereNull(SqlHelper::getPreparedField($this->filterColumns[$item->field]));
                        });
                    } else {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
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

        $entityModel->id = $entity->id;
        $entityModel->customerConfigJobActivityId = $entity->customerConfigJobActivityId;
        $entityModel->customerConfigJobActivityHazardId = $entity->customerConfigJobActivityHazard ? $entity->customerConfigJobActivityHazard->id : null;

        if ($isNewRecord) {
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

    public function bulkInsert($entity)
    {
        $authUser = $this->getAuthUser();
        $entity->createdBy = $authUser ? $authUser->id : 1;
        $entity->createdAt = Carbon::now();
        return $this->service->bulkInsert($entity);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
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
            $entity->customerConfigJobActivityId = $model->customerConfigJobActivityId;
            $entity->customerConfigJobActivityHazardId = $model->customerConfigJobActivityHazardId;

            return $entity;
        } else {
            return null;
        }
    }

    public static function create($customerConfigJobActivityId, $customerConfigJobActivityHazardId)
    {
        $self = new self();
        if (!$entityModel = $self->model->whereCustomerConfigJobActivityId($customerConfigJobActivityId)
            ->whereCustomerConfigJobActivityHazardId($customerConfigJobActivityHazardId)->first()) {
            $entityModel = new \stdClass();
            $entityModel->id = 0;
            $entityModel->customerConfigJobActivityId = $customerConfigJobActivityId;
            $entityModel->customerConfigJobActivityHazard =  new \stdClass();
            $entityModel->customerConfigJobActivityHazard->id = $customerConfigJobActivityHazardId;
            $self->insertOrUpdate($entityModel);
        }
    }
}
