<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Project;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use Carbon\Carbon;
use Exception;
use Wgroup\SystemParameter\SystemParameter;
use DB;


class CustomerProjectRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerProjectModel());

        $this->service = new CustomerProjectService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_project.id",
            "customerId" => "wg_customer_project.customer_id",
            "name" => "wg_customer_project.name",
            "type" => "wg_customer_project.type",
            "description" => "wg_customer_project.description",
            "serviceorder" => "wg_customer_project.serviceOrder",
            "defaultskill" => "wg_customer_project.defaultSkill",
            "estimatedhours" => "wg_customer_project.estimatedHours",
            "deliverydate" => "wg_customer_project.deliveryDate",
            "isrecurrent" => "wg_customer_project.isRecurrent",
            "status" => "wg_customer_project.status",
            "isbilled" => "wg_customer_project.isBilled",
            "invoicenumber" => "wg_customer_project.invoiceNumber",
            "previousId" => "wg_customer_project.previous_id",
            "item" => "wg_customer_project.item",
            "createdby" => "wg_customer_project.createdBy",
            "updatedby" => "wg_customer_project.updatedBy",
            "createdAt" => "wg_customer_project.created_at",
            "updatedAt" => "wg_customer_project.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
        $query->leftjoin("tableParent", function ($join) {
        $join->on('wg_customer_project.parent_id', '=', 'tableParent.id');
        }
         */

        $excludeMandatoryFields = ["type"];

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $applyFilter = ($excludeMandatoryFields == null || count($excludeMandatoryFields) == 0 || !in_array($item->field, $excludeMandatoryFields));

                    if ($applyFilter) {
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

    public function allActivities($criteria)
    {
        $this->updateMassStatus();

        $this->setColumns([
            'id' => 'wg_customer_project.id',
            'name' => 'wg_customer_project.name',
            'description' => 'wg_customer_project.description',
            'estimatedHours' => 'wg_customer_project.estimatedHours',
            'projectType' => 'wg_customer_project.type',
            'odes' => 'wg_customer_project.serviceOrder',
            'isBilled' => 'wg_customer_project.isBilled',
            'invoiceNumber' => 'wg_customer_project.invoiceNumber',
            'typeDescription' => 'project_type.item AS typeDescription',

            'customerName' => 'wg_customers.businessName AS customerName',

            'agentName' => 'wg_agent.name AS agentName',
            'email' => 'users.email',

            'projectAgentId' => 'wg_customer_project_agent.id AS projectAgentId',

            'administrator' => 'users2.name as administrator',
            'deliveryDate' => 'wg_customer_project.deliveryDate',

            'assignedHours' => DB::raw("ROUND(IFNULL(wg_customer_project_agent.estimatedHours, 0),0) AS assignedHours"),
            'scheduledHours' => DB::raw("ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'activo', duration, 0)), 0),0) + ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'inactivo', duration, 0)), 0),0) AS scheduledHours"),
            'runningHours' => DB::raw("ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'inactivo', duration, 0)), 0),0) AS runningHours"),

            'statusText' => DB::raw("wg_customer_project.status AS statusText"),
            'customerId' => 'wg_customers.id AS customerId',
            'arl' => 'wg_customers.arl',
            'createdBy' => 'wg_customer_project.createdBy',
            'agentId' => 'wg_customer_project_agent.agent_id',

            "countAttachment" => DB::raw("IFNULL(project_document_stats.qryAttachment, 0) AS countAttachment")
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_project.customer_id');
            })
            ->leftjoin("wg_customer_project_agent", function ($join) {
                $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
            })
            ->leftjoin("wg_agent", function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_project_agent.agent_id');
            })
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->leftjoin("users as users2", function ($join) {
                $join->on('users2.id', '=', 'wg_customer_project.createdBy');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable("project_type")), function ($join) {
                $join->on('project_type.value', '=', 'wg_customer_project.type');
            })
            ->leftJoin('wg_customer_project_agent_task as wg_customer_project_agent_stats', 'wg_customer_project_agent_stats.project_agent_id', '=', 'wg_customer_project_agent.id')
            ->leftjoin(DB::raw(CustomerProjectModel::getRelationDocumentCount('project_document_stats')), function ($join) {
                $join->on('wg_customer_project.id', '=', 'project_document_stats.project_id');
            })
            ->groupBy('wg_customer_project.id');



        $excludeMandatoryFields = ["type"];

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $applyFilter = ($excludeMandatoryFields == null || count($excludeMandatoryFields) == 0 || !in_array($item->field, $excludeMandatoryFields));

                    if ($applyFilter) {
                        if ($item->field == "isBilled" && $item->value) {
                            $query->whereRaw("(wg_customer_project.isBilled IS NULL OR wg_customer_project.isBilled = ?)", SqlHelper::getPreparedData($item));
                        } else if ($item->field == "month" && $item->value) {
                            $query->whereMonth('wg_customer_project.deliveryDate', '=', $item->value ? $item->value : Carbon::now('America/Bogota')->month);
                        } else if ($item->field == "year" && $item->value) {
                            $query->whereYear('wg_customer_project.deliveryDate', '=', $item->value ? $item->value : Carbon::now('America/Bogota')->year);
                        } else {
                            $query->when(SqlHelper::getPreparedData($item), function ($query) use ($item) {
                                $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            });
                        }
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

        $result = $this->get($query, $criteria);

        return $result;
    }

    public function updateMassStatus()
    {
        $q1 = DB::table("wg_customer_project_agent")
            ->groupBy('wg_customer_project_agent.project_id')
            ->select(
                "wg_customer_project_agent.project_id",
                DB::raw("sum(estimatedHours) estimatedHours")
            );

        $query = DB::table("wg_customer_project")
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_project.customer_id');
            })
            ->leftjoin("wg_customer_project_agent", function ($join) {
                $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
            })
            ->leftjoin("wg_agent", function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_project_agent.agent_id');
            })
            ->leftJoin('wg_customer_project_agent_task as wg_customer_project_agent_stats', 'wg_customer_project_agent_stats.project_agent_id', '=', 'wg_customer_project_agent.id')

            ->join(DB::raw("({$q1->toSql()}) as wg_customer_project_agent_sum"), function ($join) {
                $join->on('wg_customer_project_agent_sum.project_id', '=', 'wg_customer_project.id');
            })
            ->mergeBindings($q1)

            ->whereRaw("wg_customer_project.status = 'En progreso'")
            ->groupBy('wg_customer_project.id')
            ->select(
                "wg_customer_project.id",
                DB::raw("ROUND(IFNULL(wg_customer_project_agent_sum.estimatedHours, 0),0) AS assignedHours"),
                DB::raw("ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'activo', duration, 0)), 0),0) + ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'inactivo', duration, 0)), 0),0) AS scheduledHours"),
                DB::raw("ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'inactivo', duration, 0)), 0),0) AS runningHours")
            );

        DB::table("wg_customer_project")
            ->join(DB::raw("({$query->toSql()}) as customer_project"), function ($join) {
                $join->on('customer_project.id', '=', 'wg_customer_project.id');
            })
            ->mergeBindings($query)
            ->whereRaw("customer_project.runningHours >= customer_project.assignedHours")
            ->update([
                "wg_customer_project.status" => "Completada",
            ]);
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
        $entityModel->item = $entity->item;
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
            $model = (object) $model;
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
            $entity->item = $model->item;
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

    public function allTaskType()
    {
        return $this->service->allTaskType();
    }

    public function getSummary($criteria)
    {
        return $this->service->getSummary($criteria);
    }

    public function getList($criteria)
    {
        return $this->service->getList($criteria);
    }

    public function getSummaryChartPie($criteria)
    {
        return $this->service->getSummaryChartPie($criteria);
    }

    public function getContributationsVsExecutionsChartPie($criteria)
    {
        return $this->service->getContributationsVsExecutionsChartPie($criteria);
    }

    public function getContributationsVsExecutionsChartLineByMonth($criteria)
    {
        return $this->service->getContributationsVsExecutionsChartLineByMonth($criteria);
    }

    public function getAllYearsContributations($customerId)
    {
        return $this->service->getAllYearsContributations($customerId);
    }

    public function getAllUserAdministrators(int $year, int $month, $type, $customerId)
    {
        return $this->service->getAllUserAdministrators($year, $month, $type, $customerId);
    }

    public function export($criteria, $zipFilename = null)
    {
        $start = Carbon::now();

        $authUser = $this->getAuthUser();
        $criteria->email = $authUser->email;
        $criteria->name = $authUser->name;
        $criteria->userId = $authUser->id;

        Queue::push(CustomerEmployeeDocumentJob::class, ['criteria' => $criteria], 'zip');

        $end = Carbon::now();

        return [
            'message' => 'ok',
            'elapseTime' => $end->diffInSeconds($start),
            'endTime' => $end->timestamp,
            'filename' => $zipFilename,
            'path' => CmsHelper::getPublicDirectory('zip/exports/'),
            //'uids' => $data['uids']
        ];
    }
}
