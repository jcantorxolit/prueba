<?php

namespace AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation;

use AdeN\Api\Classes\BaseService;
use DB;

class CustomerConfigJobActivityHazardRelationService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function bulkInsert($entity)
    {
        $sql = "INSERT INTO wg_customer_config_job_activity_hazard_relation (`id`, `customer_config_job_activity_id`, `customer_config_job_activity_hazard_id`, `created_by`, `created_at`)
        SELECT
            NULL id,
            ? customer_config_job_activity_id,
            `wg_customer_config_job_activity_hazard`.`id` customer_config_job_activity_hazard_id,
            ? created_by,
            ? created_at
        FROM
            `wg_customer_config_job_activity_hazard`
        INNER JOIN `wg_config_job_activity_hazard_classification` ON `wg_customer_config_job_activity_hazard`.`classification` = `wg_config_job_activity_hazard_classification`.`id`
        INNER JOIN `wg_config_job_activity_hazard_description` ON `wg_customer_config_job_activity_hazard`.`description` = `wg_config_job_activity_hazard_description`.`id`
        INNER JOIN `wg_config_job_activity_hazard_effect` ON `wg_customer_config_job_activity_hazard`.`health_effect` = `wg_config_job_activity_hazard_effect`.`id`
        INNER JOIN `wg_config_job_activity_hazard_type` ON `wg_customer_config_job_activity_hazard`.`type` = `wg_config_job_activity_hazard_type`.`id`
        WHERE
            `wg_customer_config_job_activity_hazard`.`job_activity_id` = ?
        AND `wg_customer_config_job_activity_hazard`.`id` NOT IN (
            SELECT
                customer_config_job_activity_hazard_id
            FROM
                `wg_customer_config_job_activity_hazard_relation`
            WHERE
                `customer_config_job_activity_id` = ?
        )";

        DB::statement($sql, [
            $entity->customerConfigJobActivityId,
            $entity->createdBy,
            $entity->createdAt,
            $entity->jobActivityId,
            $entity->customerConfigJobActivityId
        ]);

    }
}
