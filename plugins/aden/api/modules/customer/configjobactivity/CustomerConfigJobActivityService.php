<?php

namespace AdeN\Api\Modules\Customer\ConfigJobActivity;

use AdeN\Api\Classes\BaseService;
use DB;

class CustomerConfigJobActivityService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function allList($customerId)
    {

        $query = DB::table('wg_customer_config_job_activity');

        $query->join("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job_activity.job_id', '=', 'wg_customer_config_job.id');

        })->join("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');

        })->join("wg_customer_config_activity_process", function ($join) {
            $join->on('wg_customer_config_job_activity.activity_id', '=', 'wg_customer_config_activity_process.id');

        })->join("wg_customer_config_activity", function ($join) {
            $join->on('wg_customer_config_activity_process.activity_id', '=', 'wg_customer_config_activity.id');

        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_activity_process.workplace_id', '=', 'wg_customer_config_workplace.id');

        })->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_activity_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');

        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
            $join->on('wg_customer_config_activity_process.process_id', '=', 'wg_customer_config_process.id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity.updatedby', '=', 'users.id');

        })
            ->select(
                'wg_customer_config_job_activity.id',
                'wg_customer_config_job_activity.activity_id',
                'wg_customer_config_activity.name as activity',
                'wg_customer_config_job_data.name as job'
            )
            ->where("wg_customer_config_workplace.customer_id", $customerId)
            ->where("wg_customer_config_job.status", "Activo")
            ->where("wg_customer_config_process.status", "Activo")
            ->where("wg_customer_config_macro_process.status", "Activo")
            ->where("wg_customer_config_workplace.status", "Activo")
            ->where("wg_customer_config_activity.status", "Activo");

        $data = $query->get()->toArray();

        return array_map(function ($row) {
            return [
                "id" => $row->id,
                "activityId" => $row->activity_id,
                "name" => "{$row->activity} ({$row->job})",
            ];
        }, $data);

    }

    public function findOne($id)
    {

        $query = DB::table('wg_customer_config_job_activity');

        $query->join("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job_activity.job_id', '=', 'wg_customer_config_job.id');

        })->join("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');

        })->join("wg_customer_config_activity_process", function ($join) {
            $join->on('wg_customer_config_job_activity.activity_id', '=', 'wg_customer_config_activity_process.id');

        })->join("wg_customer_config_activity", function ($join) {
            $join->on('wg_customer_config_activity_process.activity_id', '=', 'wg_customer_config_activity.id');

        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_activity_process.workplace_id', '=', 'wg_customer_config_workplace.id');

        })->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_activity_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');

        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
            $join->on('wg_customer_config_activity_process.process_id', '=', 'wg_customer_config_process.id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity.updatedby', '=', 'users.id');

        })
            ->select(
                'wg_customer_config_job_activity.id',
                'wg_customer_config_job_activity.activity_id',
                'wg_customer_config_activity.name as activity',
                'wg_customer_config_job_data.name as job'
            )
            ->where("wg_customer_config_job_activity.id", $id)
            ->where("wg_customer_config_job.status", "Activo")
            ->where("wg_customer_config_process.status", "Activo")
            ->where("wg_customer_config_macro_process.status", "Activo")
            ->where("wg_customer_config_workplace.status", "Activo")
            ->where("wg_customer_config_activity.status", "Activo");

        $data = $query->first();

        return $data ? [
            "id" => $data->id,
            "activityId" => $data->activity_id,
            "name" => "{$data->activity} ({$data->job})",
        ] : null;

    }

    public function bulkInsertCriticalActivity()
    {
        $query = "INSERT INTO wg_customer_employee_critical_activity SELECT DISTINCT
            NULL id,
            wg_customer_employee.id customer_employee_id,
            wg_customer_config_job_activity.id job_activity_id,
            wg_customer_config_job.id jobId,
            1 createdBy,
            NULL updatedBy,
            NOW() created_at,
            NULL updated_at
        FROM
            wg_customer_config_job_activity
        INNER JOIN wg_customer_config_activity_process ON wg_customer_config_job_activity.activity_id = wg_customer_config_activity_process.id
        INNER JOIN wg_customer_config_activity ON wg_customer_config_activity_process.activity_id = wg_customer_config_activity.id
        INNER JOIN wg_customer_config_job ON wg_customer_config_job_activity.job_id = wg_customer_config_job.id
        INNER JOIN wg_customer_employee ON wg_customer_employee.customer_id = wg_customer_config_job.customer_id
        AND wg_customer_employee.customer_id = wg_customer_config_activity.customer_id
        LEFT JOIN (
            SELECT
                *
            FROM
                wg_customer_employee_critical_activity
        ) wg_customer_employee_critical_activity ON wg_customer_employee_critical_activity.job_id = wg_customer_config_job.id
        AND wg_customer_employee_critical_activity.job_activity_id = wg_customer_config_job_activity.id
        AND wg_customer_employee.id = wg_customer_employee_critical_activity.customer_employee_id
        WHERE
            wg_customer_config_job.id = wg_customer_employee.job
        AND wg_customer_config_activity.isCritical = 1
        AND wg_customer_employee_critical_activity.id IS NULL";

        DB::statement( $query );
    }
}
