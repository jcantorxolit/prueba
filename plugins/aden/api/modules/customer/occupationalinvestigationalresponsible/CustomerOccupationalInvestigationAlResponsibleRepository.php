<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\OccupationalInvestigationAl\CustomerOccupationalInvestigationRepository;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Wgroup\SystemParameter\SystemParameter;

class CustomerOccupationalInvestigationAlResponsibleRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerOccupationalInvestigationAlResponsibleModel());

        $this->service = new CustomerOccupationalInvestigationAlResponsibleService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_occupational_investigation_al_responsible.id",
            "customerOccupationalInvestigationId" => "wg_customer_occupational_investigation_al_responsible.customer_occupational_investigation_id",
            "type" => "wg_customer_occupational_investigation_al_responsible.type",
            "entitytype" => "wg_customer_occupational_investigation_al_responsible.entityType",
            "entityid" => "wg_customer_occupational_investigation_al_responsible.entityId",
            "documentNumber" => "wg_customer_occupational_investigation_al_responsible.documentNumber",
            "name" => "wg_customer_occupational_investigation_al_responsible.name",
            "job" => "wg_customer_occupational_investigation_al_responsible.job",
            "role" => "wg_customer_occupational_investigation_al_responsible.role",
            "createdby" => "wg_customer_occupational_investigation_al_responsible.createdBy",
            "updatedby" => "wg_customer_occupational_investigation_al_responsible.updatedBy",
            "createdAt" => "wg_customer_occupational_investigation_al_responsible.created_at",
            "updatedAt" => "wg_customer_occupational_investigation_al_responsible.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
        $query->leftjoin("tableParent", function ($join) {
        $join->on('wg_customer_occupational_investigation_al_responsible.parent_id', '=', 'tableParent.id');
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

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "responsible.id",
            "type" => "wg_occupational_investigation_responsible_type.item AS type",
            "documentNumber" => "responsible.documentNumber",
            "fullName" => "responsible.fullName",
            "job" => "responsible.job",
            "typeCode" => "responsible.type AS typeCode",
            "customerId" => "responsible.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_employee')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_employee.customer_id');
            })->join("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })->leftjoin("wg_customer_config_job", function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');
            })->leftjoin("wg_customer_config_job_data", function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })
            ->select(
                'wg_customer_employee.id',
                'wg_customer_employee.customer_id',
                DB::raw("'Employee' AS type"),
                'wg_employee.documentNumber',
                'wg_employee.fullName',
                'wg_customer_config_job_data.name AS job'
            );

        //SPRINT 14: ONLY EMPLOYEE
        // $q2 = DB::table('wg_customer_user')
        //     ->join('wg_customers', function ($join) {
        //         $join->on('wg_customers.id', '=', 'wg_customer_user.customer_id');
        //     })
        //     ->select(
        //         'wg_customer_user.id',
        //         'wg_customer_user.customer_id',
        //         DB::raw("'User' AS type"),
        //         'wg_customer_user.documentNumber',
        //         'wg_customer_user.fullName',
        //         DB::raw("NULL AS job")
        //     );

        //SPRINT 14: ONLY EMPLOYEE
        // $q3 = DB::table('wg_customer_agent')
        //     ->join('wg_customers', function ($join) {
        //         $join->on('wg_customers.id', '=', 'wg_customer_agent.customer_id');
        //     })
        //     ->join('wg_agent', function ($join) {
        //         $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
        //     })
        //     ->select(
        //         'wg_agent.id',
        //         'wg_customer_agent.customer_id',
        //         DB::raw("'Agent' AS type"),
        //         'wg_agent.documentNumber',
        //         'wg_agent.name',
        //         DB::raw("NULL AS job")
        //     )
        //     ->groupBy("wg_agent.id");


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customer_employee.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        //$q2->whereRaw(SqlHelper::getPreparedField('wg_customer_user.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        //$q3->whereRaw(SqlHelper::getPreparedField('wg_customer_agent.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        //SPRINT 14: ONLY EMPLOYEE
        //$q1->union($q2)->union($q3);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as responsible")))
            ->mergeBindings($q1);

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_occupational_investigation_responsible_type')), function ($join) {
            $join->on('responsible.type', '=', 'wg_occupational_investigation_responsible_type.value');
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

    public function findRelation($id, $type, $customerId = null)
    {
        $q1 = DB::table('wg_customer_employee')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_employee.customer_id');
            })->join("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })->leftjoin("wg_customer_config_job", function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');
            })->leftjoin("wg_customer_config_job_data", function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })
            ->select(
                'wg_customer_employee.id',
                'wg_customer_employee.customer_id',
                DB::raw("'Employee' AS type"),
                'wg_employee.documentNumber',
                'wg_employee.fullName',
                'wg_customer_config_job_data.name AS job'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_user.customer_id');
            })
            ->select(
                'wg_customer_user.id',
                'wg_customer_user.customer_id',
                DB::raw("'User' AS type"),
                'wg_customer_user.documentNumber',
                'wg_customer_user.fullName',
                DB::raw("NULL AS job")
            );

        $q3 = DB::table('wg_customer_agent')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_agent.customer_id');
            })
            ->join('wg_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->select(
                'wg_agent.id',
                'wg_customer_agent.customer_id',
                DB::raw("'Agent' AS type"),
                'wg_agent.documentNumber',
                'wg_agent.name',
                DB::raw("NULL AS job")
            );

        if ($customerId != null) {
            $q1->where(SqlHelper::getPreparedField('wg_customer_employee.customer_id'), $customerId);
            $q2->where(SqlHelper::getPreparedField('wg_customer_user.customer_id'), $customerId);
            $q3->where(SqlHelper::getPreparedField('wg_customer_agent.customer_id'), $customerId);
        }

        $q1->union($q2)->mergeBindings($q2)->union($q3)->mergeBindings($q3);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as responsible")))
            ->mergeBindings($q1)
            ->where("responsible.id", $id)
            ->where("responsible.type", $type);

        return $query->first();
    }

    public function findByParent($id)
    {
        return $this->parseModelsWithRelations($this->model->whereCustomerOccupationalInvestigationId($id)->get());
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_occupational_investigation_id = $entity->customerOccupationalInvestigationId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->entitytype = $entity->responsible ? $entity->responsible->type : null;
        $entityModel->entityid = $entity->responsible ? $entity->responsible->id : null;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->name = $entity->name;
        $entityModel->job = $entity->job;
        //$entityModel->role = $entity->role ? $entity->role : null;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;

            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function bulkInsertOrUpdate($records, $entityId)
    {
        foreach ($records as $record) {
            $record->customerOccupationalInvestigationId = $entityId;
            $this->insertOrUpdate($record);
        }

        return true;
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

            $parent = (new CustomerOccupationalInvestigationRepository)->find($model->customer_occupational_investigation_id);

            $entity->id = $model->id;
            $entity->customerOccupationalInvestigationId = $model->customer_occupational_investigation_id;
            $entity->type = $model->getType();
            $entity->responsible = $this->findRelation($model->entityId, $model->entityType, $parent->customer_id);
            $entity->documentNumber = $model->documentNumber;
            $entity->name = $model->name;
            $entity->job = $model->job;

            return $entity;
        } else {
            return null;
        }
    }

    public function parseModelsWithRelations($models)
    {
        $modelClass = get_class($this->model);

        if (is_array($models) || $models instanceof Collection || $models instanceof \October\Rain\Support\Collection) {
            $parsed = array();
            foreach ($models as $model) {
                if ($model instanceof $modelClass) {
                    $parsed[] = $this->parseModelWithRelations($model);
                } else {
                    $parsed[] = $model;
                }
            }
            return $parsed;
        } else {
            return null;
        }
    }
}
