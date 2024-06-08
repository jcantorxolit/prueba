<?php

namespace AdeN\Api\Modules\Customer\ProjectHistorial;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use DB;
use Exception;
use Log;

class CustomerProjectHistorialRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerProjectHistorialModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "reason" => "wg_customer_project_historial.reason",
            "createdBy" => "users.name AS createdBy",
            "createdAt" => "wg_customer_project_historial.created_at",
            "id" => "wg_customer_project_historial.id",
            "customerProjectId" => "wg_customer_project_historial.customer_project_id as customerProjectId",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->leftjoin("users", 'users.id', '=', 'wg_customer_project_historial.created_by');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function insertOrUpdate($entity)
    {
        try {
            DB::beginTransaction();

            $authUser = $this->getAuthUser();
            $userId = $authUser->id ?? 1;

            if (!($entityModel = $this->find($entity->id))) {
                $entityModel = $this->model->newInstance();
            }

            $entityModel->customerProjectId = $entity->customerProjectId;
            $entityModel->reason = $entity->reason;
            $entityModel->type = $entity->type;
            $entityModel->deliveryDate = $entity->deliveryDate ? Carbon::parse($entity->deliveryDate)->subHours(7) : null;

            if (empty($entityModel->id)) {
                $entityModel->createdBy = $userId;
            }

            $entityModel->updatedBy = $userId;
            $entityModel->save();

            if ($entityModel->type == "C") {
                DB::table("wg_customer_project")
                    ->where("id", $entity->customerProjectId)
                    ->update([
                        "status" => "Cancelada",
                        "updated_at" => Carbon::now(),
                        "updatedBy" => $userId
                    ]);

                DB::table("wg_customer_project_costs")
                    ->where("project_id", $entity->customerProjectId)
                    ->delete();

                DB::table("wg_customer_project_agent_consolidate")
                    ->join("wg_customer_project", function ($join) {
                        $join->on('wg_customer_project_agent_consolidate.project_id', '=', 'wg_customer_project.id');
                    })
                    ->where("wg_customer_project.id", $entity->customerProjectId)
                    ->delete();

                DB::table("wg_customer_project_agent_task_tracking")
                    ->join("wg_customer_project_agent_task", function ($join) {
                        $join->on('wg_customer_project_agent_task.id', '=', 'wg_customer_project_agent_task_tracking.project_agent_task_id');
                    })
                    ->join("wg_customer_project_agent", function ($join) {
                        $join->on('wg_customer_project_agent.id', '=', 'wg_customer_project_agent_task.project_agent_id');
                    })
                    ->where("wg_customer_project_agent_task.id", $entity->customerProjectId)
                    ->delete();

                DB::table("wg_customer_project_agent_task")
                    ->join("wg_customer_project_agent", function ($join) {
                        $join->on('wg_customer_project_agent.id', '=', 'wg_customer_project_agent_task.project_agent_id');
                    })
                    ->where("wg_customer_project_agent_task.id", $entity->customerProjectId)
                    ->delete();

                DB::table("wg_customer_project_agent")
                    ->join("wg_customer_project", function ($join) {
                        $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
                    })
                    ->where("wg_customer_project.id", $entity->customerProjectId)
                    ->delete();
            } else if ($entityModel->type == "R") {
                DB::table("wg_customer_project")
                    ->where("id", $entity->customerProjectId)
                    ->update([
                        "deliveryDate" => $entity->deliveryDate ? Carbon::parse($entity->deliveryDate) : null,
                        "updated_at" => Carbon::now(),
                        "updatedBy" => $userId
                    ]);
            }

            DB::commit();

            return $this->parseModelWithRelations($entityModel);
            
        } catch (\Exception $ex) {
            DB::rollback();
            Log::error($ex);
            throw $ex;
        }
    }


    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }


    public function parseModelWithRelations(CustomerProjectHistorialModel $model)
    {
        $entity = new \stdClass();
        $entity->id = $model->id;
        $entity->customerProjectId = $model->customerProjectId;
        $entity->reason = $model->reason;
        $entity->type = $model->type;
        $entity->deliveryDate = $model->deliveryDate;
        return $entity;
    }
}
