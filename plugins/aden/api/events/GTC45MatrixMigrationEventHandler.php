<?php

namespace AdeN\Api\Events;

use DB;
use Log;

/**
 * Declare an class to manage the migration of the matrix gtc45 to express
 */
class GTC45MatrixMigrationEventHandler
{
    public function handle($data)
    {
        DB::transaction(function () use ($data) {
            $this->createMacroprocessIfNotExists($data);
            $this->updateGTCMacroprocessInProcess($data);
            $this->migrateProcess($data);
            $this->migrateJob($data);
            $this->migrateActivity($data);
            $this->migrateJobRelation($data);
            //$this->migrateActivityRelation($data);
            //$this->migrateJobActivityRelation($data);
        });
    }

    private function createMacroprocessIfNotExists($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->leftjoin('wg_customer_config_macro_process', function ($join) {
                $join->where('wg_customer_config_macro_process.name', '=', 'GENERAL');
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_workplace.id AS workplace_id',
                DB::raw("'GENERAL' AS name"),
                DB::raw("'Activo' AS status"),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_macro_process.id')
            ->where('wg_customer_config_workplace.customer_id', $data->customerId)
            ->groupBy(
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_workplace.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_macro_process` (`id`, `customer_id`, `workplace_id`, `name`, `status`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function updateGTCMacroprocessInProcess($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->where('wg_customer_config_macro_process.name', '=', 'GENERAL');
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_workplace.id AS workplace_id',
                'wg_customer_config_macro_process.id AS macroprocess_id'
            )
            ->where('wg_customer_config_workplace.customer_id', $data->customerId)
            ->groupBy(
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_workplace.id'
            );

        return DB::table('wg_customer_config_process_express_relation')
            ->join(DB::raw("({$query->toSql()}) AS wg_customer_config_macroprocess"), function ($join) {
                $join->on('wg_customer_config_macroprocess.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_macroprocess.workplace_id', '=', 'wg_customer_config_process_express_relation.customer_workplace_id');
            })
            ->mergeBindings($query)
            ->where('wg_customer_config_process_express_relation.customer_id', $data->customerId)
            ->whereNull('wg_customer_config_process_express_relation.gtc_customer_macroprocess_id')
            ->update([
                'wg_customer_config_process_express_relation.gtc_customer_macroprocess_id' => DB::raw('wg_customer_config_macroprocess.macroprocess_id')
            ]);
    }

    private function migrateProcess($data)
    {
        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })

            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.gtc_customer_macroprocess_id', '=', 'wg_customer_config_macro_process.id');
            })

            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
            })

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })

            ->leftjoin('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_process.name', '=', 'wg_customer_config_process_express.name');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_macro_process.customer_id',
                'wg_customer_config_macro_process.workplace_id',
                'wg_customer_config_macro_process.id  AS macro_process_id',
                'wg_customer_config_process_express.name',
                DB::raw("'Activo' AS status"),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_process.id')
            ->where('wg_customer_config_workplace.customer_id', $data->customerId);
        //->groupBy('wg_customer_config_process_express.name');

        $sql = 'INSERT INTO `wg_customer_config_process` (`id`, `customer_id`, `workplace_id`, `macro_process_id`, `name`, `status`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateJob($data)
    {
        $query = DB::table('wg_customer_config_job_express')

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->leftjoin('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.name', '=', 'wg_customer_config_job_express.name');
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job_express.customer_id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_job_express.customer_id',
                'wg_customer_config_job_express.name',
                DB::raw("'Activo' AS status"),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_job_data.id')
            ->where('wg_customer_config_job_express.customer_id', $data->customerId)
            ->where('wg_customer_config_job_express.status', 1)
            ->groupBy('wg_customer_config_job_express.name');

        $sql = 'INSERT INTO `wg_customer_config_job_data` (`id`, `customer_id`, `name`, `status`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateActivity($data)
    {
        $query = DB::table('wg_customer_config_activity_express')

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })
            ->leftjoin('wg_customer_config_activity', function ($join) {
                $join->on('wg_customer_config_activity.name', '=', 'wg_customer_config_activity_express.name');
                $join->on('wg_customer_config_activity.customer_id', '=', 'wg_customer_config_activity_express.customer_id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_activity_express.customer_id',
                'wg_customer_config_activity_express.name',
                DB::raw("'Activo' AS status"),
                DB::raw('0 AS is_critical'),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_activity.id')
            ->where('wg_customer_config_activity_express.customer_id', $data->customerId)
            ->where('wg_customer_config_activity_express.status', 1)
            ->groupBy('wg_customer_config_activity_express.name');

        $sql = 'INSERT INTO `wg_customer_config_activity` (`id`, `customer_id`, `name`, `status`, `isCritical`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)  ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateJobRelation($data)
    {
        $qJob = $this->prepareConfigJobQuery($data);
        $qJobProcess = $this->prepareConfigJobProcessQuery($data);

        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })

            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.gtc_customer_macroprocess_id', '=', 'wg_customer_config_macro_process.id');
            })

            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
            })

            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })

            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
            })

            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job_express.customer_id');
                $join->on('wg_customer_config_job_data.name', '=', 'wg_customer_config_job_express.name');
            })

            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_process.name', '=', 'wg_customer_config_process_express.name');
            })

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })

            /*->leftjoin('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
            })*/
            ->leftjoin(DB::raw("({$qJob->toSql()}) as wg_customer_config_job"), function ($join) {
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
            })
            ->mergeBindings($qJob)

            ->leftjoin(DB::raw("({$qJobProcess->toSql()}) as wg_customer_config_job_sub"), function ($join) {
                $join->on('wg_customer_config_job_sub.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job_sub.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job_sub.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job_sub.process_id', '=', 'wg_customer_config_process.id');                
            })
            ->mergeBindings($qJobProcess)

            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_process.customer_id',
                'wg_customer_config_process.workplace_id',
                'wg_customer_config_process.macro_process_id',
                'wg_customer_config_process.id AS process_id',
                'wg_customer_config_job_data.id AS job_id',
                DB::raw("'Activo' AS status"),
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_job.id')
            ->whereNull('wg_customer_config_job_sub.job_name')
            ->where('wg_customer_config_workplace.customer_id', $data->customerId)
            ->groupBy(
                'wg_customer_config_process.customer_id',
                'wg_customer_config_process.workplace_id',
                'wg_customer_config_process.macro_process_id',
                'wg_customer_config_process.id',
                'wg_customer_config_job_data.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_job` (`id`, `customer_id`, `workplace_id`, `macro_process_id`, `process_id`, `job_id`, `status`, `createdBy`, `updatedBy`, `created_at`, `updated_at`) ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateActivityRelation($data)
    {
        $qJob = $this->prepareConfigJobQuery($data);        

        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })

            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.gtc_customer_macroprocess_id', '=', 'wg_customer_config_macro_process.id');
            })

            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
            })

            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })

            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
            })

            ->join('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
            })

            ->join('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_activity_express_relation.customer_id');
                $join->on('wg_customer_config_activity_express.id', '=', 'wg_customer_config_activity_express_relation.customer_activity_express_id');
            })

            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job_express.customer_id');
                $join->on('wg_customer_config_job_data.name', '=', 'wg_customer_config_job_express.name');
            })

            ->join('wg_customer_config_activity', function ($join) {
                $join->on('wg_customer_config_activity.customer_id', '=', 'wg_customer_config_activity_express.customer_id');
                $join->on('wg_customer_config_activity.name', '=', 'wg_customer_config_activity_express.name');
            })

            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_process.name', '=', 'wg_customer_config_process_express.name');
            })

            /*->join('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
            })*/
            ->join(DB::raw("({$qJob->toSql()}) as wg_customer_config_job"), function ($join) {
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
            })
            ->mergeBindings($qJob)

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })

            ->leftjoin('wg_customer_config_activity_process', function ($join) {
                $join->on('wg_customer_config_activity_process.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_activity_process.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_activity_process.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_activity_process.activity_id', '=', 'wg_customer_config_activity.id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_activity.id AS activity_id',
                'wg_customer_config_process.workplace_id',
                'wg_customer_config_process.macro_process_id',
                'wg_customer_config_process.id AS process_id',
                'wg_customer_config_activity_express_relation.is_routine',
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_activity_process.id')
            ->where('wg_customer_config_workplace.customer_id', $data->customerId)
            ->groupBy(
                'wg_customer_config_process.customer_id',
                'wg_customer_config_process.workplace_id',
                'wg_customer_config_process.macro_process_id',
                'wg_customer_config_process.id',
                'wg_customer_config_activity.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_activity_process` (`id`, `activity_id`, `workplace_id`, `macro_process_id`, `process_id`, `isRoutine`, `createdBy`, `updatedBy`, `created_at`, `updated_at`) ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function migrateJobActivityRelation($data)
    {
        $qJob = $this->prepareConfigJobQuery($data);
        //$qActivity = $this->prepareConfigActivityQuery($data);

        $query = DB::table('wg_customer_config_workplace')

            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })

            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.gtc_customer_macroprocess_id', '=', 'wg_customer_config_macro_process.id');
            })

            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
            })

            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })

            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
            })

            ->join('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
            })

            ->join('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_activity_express_relation.customer_id');
                $join->on('wg_customer_config_activity_express.id', '=', 'wg_customer_config_activity_express_relation.customer_activity_express_id');
            })

            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job_express.customer_id');
                $join->on('wg_customer_config_job_data.name', '=', 'wg_customer_config_job_express.name');
            })

            ->join('wg_customer_config_activity', function ($join) {
                $join->on('wg_customer_config_activity.customer_id', '=', 'wg_customer_config_activity_express.customer_id');
                $join->on('wg_customer_config_activity.name', '=', 'wg_customer_config_activity_express.name');
            })

            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.customer_id', '=', 'wg_customer_config_macro_process.customer_id');
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_process.name', '=', 'wg_customer_config_process_express.name');
            })


            /*->join('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
            })*/
            ->join(DB::raw("({$qJob->toSql()}) as wg_customer_config_job"), function ($join) {
                $join->on('wg_customer_config_job.customer_id', '=', 'wg_customer_config_process.customer_id');
                $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
            })
            ->mergeBindings($qJob)

            ->join('wg_customer_config_activity_process', function ($join) {
                $join->on('wg_customer_config_activity_process.workplace_id', '=', 'wg_customer_config_process.workplace_id');
                $join->on('wg_customer_config_activity_process.macro_process_id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_activity_process.process_id', '=', 'wg_customer_config_process.id');
                $join->on('wg_customer_config_activity_process.activity_id', '=', 'wg_customer_config_activity.id');
            })

            ->join("users", function ($join) use ($data) {
                $join->where('users.id', '=', $data->updatedBy);
            })

            ->leftjoin('wg_customer_config_job_activity', function ($join) {
                $join->on('wg_customer_config_job_activity.job_id', '=', 'wg_customer_config_job.id');
                $join->on('wg_customer_config_job_activity.activity_id', '=', 'wg_customer_config_activity_process.id');
            })
            ->select(
                DB::raw('NULL AS id'),
                'wg_customer_config_job.id AS job_id',
                'wg_customer_config_activity_process.id AS activity_id',
                'users.id AS created_by',
                DB::raw('NULL AS updated_by'),
                DB::raw('NOW() AS created_at'),
                DB::raw('NULL AS updated_at')
            )
            ->whereNull('wg_customer_config_job_activity.id')
            ->where('wg_customer_config_workplace.customer_id', $data->customerId)
            ->groupBy(
                'wg_customer_config_job.id',
                'wg_customer_config_activity_process.id'
            );

        $sql = 'INSERT INTO `wg_customer_config_job_activity` (`id`, `job_id`, `activity_id`, `createdBy`, `updatedBy`, `created_at`, `updated_at`) ' . $query->toSql();

        //Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    private function prepareConfigJobQuery($data)
    {
        return DB::table('wg_customer_config_job')
            ->where('customer_id', $data->customerId);
    }

    private function prepareConfigJobProcessQuery($data)
    {
        return DB::table('wg_customer_config_job')
            ->join("wg_customer_config_job_data", function ($join) use ($data) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
                $join->on('wg_customer_config_job_data.customer_id', '=', 'wg_customer_config_job.customer_id');
            })
            ->select(
                'wg_customer_config_job.customer_id',
                'wg_customer_config_job.workplace_id',
                'wg_customer_config_job.macro_process_id',
                'wg_customer_config_job.process_id',
                'wg_customer_config_job.job_id',
                'wg_customer_config_job_data.name AS job_name'
            )
            ->where('wg_customer_config_job.customer_id', $data->customerId);
    }

    private function prepareConfigActivityQuery($data)
    {
        return DB::table('wg_customer_config_activity')
            ->where('customer_id', $data->customerId);
    }
}
