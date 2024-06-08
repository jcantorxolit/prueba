<?php

namespace AdeN\Api\Events;

use DB;
use Log;

/**
 * Declare an class to manage the migration of the matrix gtc45 to express
 */
class ExpressMatrixMigrationEventHandler
{
    public function handle($data)
    {
        DB::transaction(function () use ($data) {
            $this->migrateProcess($data);
            $this->migrateProcessRelation($data);
            $this->migrateJob($data);
            $this->migrateJobRelation($data);
            $this->migrateActivity($data);
            $this->migrateActivityRelation($data);
            $this->updateJobIsFullyConfigured($data);
            $this->updateProcessIsFullyConfigured($data);
            $this->updateWorkplaceIsFullyConfigured($data);
        });
    }

    private function migrateProcess($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })
            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->leftjoin('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.name', '=', 'wg_customer_config_process.name');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process.customer_id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_process.customer_id',
                'wg_customer_config_process.name',
                DB::raw('1 AS status'),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_process_express.id')
            ->where('wg_customer_config_process.customer_id', $data->id)
            ->groupBy('wg_customer_config_process.name');

        $sql = 'INSERT INTO `wg_customer_config_process_express` (`id`, `customer_id`, `name`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateProcessRelation($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })
            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.name', '=', 'wg_customer_config_process.name');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process.customer_id');
            })
            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->leftjoin('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_process_express_id', '=', 'wg_customer_config_process_express.id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_process.customer_id',
                'wg_customer_config_workplace.id AS workplace_id',
                'wg_customer_config_process_express.id AS customer_process_express_id',
                DB::raw('0 AS is_fully_configured'),
                'wg_customer_config_macro_process.id AS gtc_customer_macroprocess_id',
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_process_express_relation.id')
            ->where('wg_customer_config_process.customer_id', $data->id)
            ->groupBy(
                'wg_customer_config_process.customer_id',
                'wg_customer_config_workplace.id',
                'wg_customer_config_process_express.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_process_express_relation` (`id`, `customer_id`, `customer_workplace_id`, `customer_process_express_id`, `is_fully_configured`, `gtc_customer_macroprocess_id`, `created_by`, `updated_by`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateJob($data)
    {
        $query = DB::table('wg_customer_config_job_data')

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->leftjoin('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.name', '=', 'wg_customer_config_job_data.name');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_data.customer_id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_job_data.customer_id',
                'wg_customer_config_job_data.name',
                DB::raw('1 AS status'),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_job_express.id')
            ->where('wg_customer_config_job_data.customer_id', $data->id)
            ->where('wg_customer_config_job_data.status', 'Activo')
            ->groupBy('wg_customer_config_job_data.name');

        $sql = 'INSERT INTO `wg_customer_config_job_express` (`id`, `customer_id`, `name`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateJobRelation($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })
            ->join('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })

            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job.customer_id');
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })

            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.name', '=', 'wg_customer_config_process.name');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process.customer_id');
            })

            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_process_express.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_process_express_id', '=', 'wg_customer_config_process_express.id');
            })

            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.name', '=', 'wg_customer_config_job_data.name');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_data.customer_id');
            })

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })

            ->leftjoin('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_job_express.customer_id');
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
                $join->on('wg_customer_config_job_express_relation.customer_job_express_id', '=', 'wg_customer_config_job_express.id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_job.customer_id',
                'wg_customer_config_process_express_relation.id AS customer_process_express_relation_id',
                'wg_customer_config_job_express.id AS customer_job_express_id',
                DB::raw('0 AS is_fully_configured'),
                DB::raw('1 AS is_active'),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_job_express_relation.id')
            ->where('wg_customer_config_job.customer_id', $data->id)
            ->groupBy(
                'wg_customer_config_job_express.customer_id',
                'wg_customer_config_process_express_relation.id',
                'wg_customer_config_job_express.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_job_express_relation` (`id`, `customer_id`, `customer_process_express_relation_id`, `customer_job_express_id`, `is_fully_configured`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateActivity($data)
    {
        $query = DB::table('wg_customer_config_activity')

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->leftjoin('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.name', '=', 'wg_customer_config_activity.name');
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_activity.customer_id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_activity.customer_id',
                'wg_customer_config_activity.name',
                DB::raw('1 AS status'),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_activity_express.id')
            ->where('wg_customer_config_activity.customer_id', $data->id)
            ->where('wg_customer_config_activity.status', 'Activo')
            ->groupBy('wg_customer_config_activity.name');

        $sql = 'INSERT INTO `wg_customer_config_activity_express` (`id`, `customer_id`, `name`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateActivityRelation($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })

            ->join('wg_customer_config_activity_process', function ($join) {
                $join->on('wg_customer_config_activity_process.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_activity_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_activity_process.workplace_id', '=', 'wg_customer_config_workplace.id');                
            })

            ->join('wg_customer_config_activity', function ($join) {
                $join->on('wg_customer_config_activity.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_activity.id', '=', 'wg_customer_config_activity_process.activity_id');
            })

            ->join('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })

            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job.customer_id');
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })

            ->join('wg_customer_config_job_activity', function ($join) {
                $join->on('wg_customer_config_job_activity.activity_id', '=', 'wg_customer_config_activity_process.id');
                $join->on('wg_customer_config_job_activity.job_id', '=', 'wg_customer_config_job.id');
            })

            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.name', '=', 'wg_customer_config_process.name');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process.customer_id');
            })

            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_process_express.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_process_express_id', '=', 'wg_customer_config_process_express.id');
            })

            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })

            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
                $join->on('wg_customer_config_job_express.name', '=', 'wg_customer_config_job_data.name');
            })

            ->join('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.name', '=', 'wg_customer_config_activity.name');
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })

            ->leftjoin('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_id', '=', 'wg_customer_config_activity_express.customer_id');
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
                $join->on('wg_customer_config_activity_express_relation.customer_activity_express_id', '=', 'wg_customer_config_activity_express.id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_job_express_relation.id AS customer_job_express_relation_id',
                'wg_customer_config_activity_express.id AS customer_activity_express_id',
                'wg_customer_config_activity_process.isRoutine AS is_routine',
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_activity_express_relation.id')
            ->where('wg_customer_config_activity_express.customer_id', $data->id)
            ->groupBy(
                'wg_customer_config_activity_express.customer_id',
                'wg_customer_config_job_express_relation.id',
                'wg_customer_config_activity_express.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_activity_express_relation` (`id`, `customer_id`, `customer_job_express_relation_id`, `customer_activity_express_id`, `is_routine`, `created_by`, `updated_by`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    public function updateJobIsFullyConfigured($data)
    {
        return DB::table('wg_customer_config_job_express_relation')
            ->leftjoin('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
            })
            ->where('wg_customer_config_job_express_relation.is_active', 1)
            ->where('wg_customer_config_job_express_relation.customer_id', $data->id)
            ->update([
                'wg_customer_config_job_express_relation.is_fully_configured' => DB::raw('CASE WHEN wg_customer_config_activity_express_relation.id IS NOT NULL THEN 1 ELSE 0 END'),
                'wg_customer_config_job_express_relation.updated_by' => $data->updatedBy,
                'wg_customer_config_job_express_relation.updated_at' => DB::raw('NOW()')
            ]);
    }

    public function updateProcessIsFullyConfigured($data)
    {
        $qJobRelation = DB::table('wg_customer_config_job_express_relation')
            ->select(
                'wg_customer_config_job_express_relation.customer_process_express_relation_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_job_express_relation.is_fully_configured THEN 1 ELSE 0 END) qtyJobFullyConfigured'),
                DB::raw('COUNT(*) qtyJob')
            )
            ->groupBy('wg_customer_config_job_express_relation.customer_process_express_relation_id');

        $query = DB::table('wg_customer_config_process_express_relation')
            ->leftjoin('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })
            ->leftjoin(DB::raw("({$qJobRelation->toSql()}) AS wg_customer_config_job_express_relation_group"), function ($join) {
                $join->on('wg_customer_config_job_express_relation_group.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })
            ->mergeBindings($qJobRelation)
            ->select(
                'wg_customer_config_process_express_relation.id',
                DB::raw('SUM(CASE WHEN wg_customer_config_job_express_relation.customer_process_express_relation_id IS NOT NULL THEN 1 ELSE 0 END) qty'),
                DB::raw('CASE WHEN wg_customer_config_job_express_relation_group.qtyJob = wg_customer_config_job_express_relation_group.qtyJobFullyConfigured AND wg_customer_config_job_express_relation_group.qtyJobFullyConfigured > 0 THEN 1 ELSE 0 END is_process_fully_configured')
            )
            ->where('wg_customer_config_process_express_relation.customer_id', $data->id)
            ->whereNotNull('wg_customer_config_job_express_relation_group.customer_process_express_relation_id')
            ->groupBy('wg_customer_config_process_express_relation.id');


        return DB::table('wg_customer_config_process_express_relation')
            ->leftjoin(DB::raw("({$query->toSql()}) AS wg_customer_config_process_express_relation_fully"), function ($join) {
                $join->on('wg_customer_config_process_express_relation_fully.id', '=', 'wg_customer_config_process_express_relation.id');
            })
            ->mergeBindings($query)
            ->where('wg_customer_config_process_express_relation.customer_id', $data->id)
            ->update([
                'wg_customer_config_process_express_relation.is_fully_configured' => DB::raw('CASE WHEN wg_customer_config_process_express_relation_fully.is_process_fully_configured IS NOT NULL THEN wg_customer_config_process_express_relation_fully.is_process_fully_configured ELSE 0 END'),
                'wg_customer_config_process_express_relation.updated_by' => DB::raw($data->updatedBy),
                'wg_customer_config_process_express_relation.updated_at' => DB::raw('NOW()')
            ]);
    }

    public function updateWorkplaceIsFullyConfigured($data)
    {
        $qProcessRelation = DB::table('wg_customer_config_process_express_relation')
            ->select(
                'wg_customer_config_process_express_relation.customer_workplace_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_process_express_relation.is_fully_configured THEN 1 ELSE 0 END) qtyProcessFullyConfigured'),
                DB::raw('COUNT(*) qtyProcess')
            )
            ->groupBy('wg_customer_config_process_express_relation.customer_workplace_id');

        $query = DB::table('wg_customer_config_workplace')
            ->leftjoin('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->leftjoin(DB::raw("({$qProcessRelation->toSql()}) AS wg_customer_config_process_express_relation_group"), function ($join) {
                $join->on('wg_customer_config_process_express_relation_group.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->mergeBindings($qProcessRelation)
            ->select(
                'wg_customer_config_workplace.id',
                DB::raw('SUM(CASE WHEN wg_customer_config_process_express_relation.customer_workplace_id IS NOT NULL THEN 1 ELSE 0 END) qty'),
                DB::raw('CASE WHEN wg_customer_config_process_express_relation_group.qtyProcess = wg_customer_config_process_express_relation_group.qtyProcessFullyConfigured AND wg_customer_config_process_express_relation_group.qtyProcessFullyConfigured > 0 THEN 1 ELSE 0 END is_workplace_fully_configured')
            )
            ->where('wg_customer_config_workplace.customer_id', $data->id)
            ->whereNotNull('wg_customer_config_process_express_relation_group.customer_workplace_id')
            ->groupBy('wg_customer_config_workplace.id');


        return DB::table('wg_customer_config_workplace')
            ->leftjoin(DB::raw("({$query->toSql()}) AS wg_customer_config_workplace_fully_configured"), function ($join) {
                $join->on('wg_customer_config_workplace_fully_configured.id', '=', 'wg_customer_config_workplace.id');
            })
            ->mergeBindings($query)
            ->where('wg_customer_config_workplace.customer_id', $data->id)
            ->update([
                'wg_customer_config_workplace.is_fully_configured' => DB::raw("CASE WHEN wg_customer_config_workplace.address IS NOT NULL 
                    AND wg_customer_config_workplace.address <> ''
                    AND wg_customer_config_workplace.economic_activity_id IS NOT NULL 
                    AND wg_customer_config_workplace.employee_contractor <> 0
                    AND wg_customer_config_workplace.employee_mision <> 0
                    AND wg_customer_config_workplace.employee_direct <> 0
                    AND wg_customer_config_workplace_fully_configured.is_workplace_fully_configured IS NOT NULL 
                    THEN wg_customer_config_workplace_fully_configured.is_workplace_fully_configured ELSE 0 END"),
                'wg_customer_config_workplace.updated_by' => DB::raw($data->updatedBy),
                'wg_customer_config_workplace.updated_at' => DB::raw('NOW()')
            ]);
    }
}
