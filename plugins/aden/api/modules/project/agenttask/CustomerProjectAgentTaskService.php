<?php

namespace AdeN\Api\Modules\Project\AgentTask;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Carbon\Carbon;
use Wgroup\Traits\UserSecurity;

class CustomerProjectAgentTaskService extends BaseService
{
    use UserSecurity;

    function __construct()
    {
        parent::__construct();
    }

    public function getListTimeLine($criteria)
    {
        $query = DB::table("wg_customer_project")
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_project.customer_id');
            })
            ->join("wg_customer_project_agent", function ($join) {
                $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
            })
            ->join("wg_agent", function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_project_agent.agent_id');
            })
            ->join("wg_customer_project_agent_task", function ($join) {
                $join->on('wg_customer_project_agent_task.project_agent_id', '=', 'wg_customer_project_agent.id');
            })
            ->select(
                'wg_customer_project_agent_task.id',
                'wg_customer_project_agent_task.project_agent_id',
                'wg_customer_project_agent_task.task',
                'wg_customer_project_agent_task.observation',
                'wg_customer_project_agent_task.startDateTime',
                'wg_customer_project_agent_task.type'
            )
            ->orderBy("wg_customer_project_agent_task.startDateTime", "DESC");

        if (!empty($criteria->administrator)) {
            $query->where('wg_customer_project.createdBy', $criteria->administrator);
        }

        if (!empty($criteria->agentId)) {
            $query->where("wg_customer_project_agent.agent_id", $criteria->agentId);
        } else {
            $this->run();
            $query->where("wg_customer_project_agent.agent_id", $this->isUserRelatedAgent());
        }

        return array_map(function ($row) {
            return [
                "id" => $row->id,
                "projectAgentId" => $row->project_agent_id,
                "description" => $row->task,
                "type" => $row->type == "01" ? "timeline-item success" : "timeline-item",
                "time" => $row->startDateTime ? Carbon::parse($row->startDateTime)->format('d/m/Y') : null,
            ];
        }, $query->get()->toArray());
    }
}
