<?php

namespace Wgroup\CustomerConfigJobActivity;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigJobActivityService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    function __construct() {
       // $this->customerRepository = new CustomerRepository();
    }

    public function init() {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $jobId) {

        $model = new CustomerConfigJobActivity();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_activity.name',
            'wg_customer_config_activity.status'
        ];

        $i = 0;

        foreach ($sorting as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->customerConfigWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_config_job_activity.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity.job_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_activity.name', $search);
            $filters[] = array('wg_customer_config_activity.status', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $jobId) {

        $model = new CustomerConfigJobActivity();
        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity.job_id', $jobId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_config_activity.name', $search);
            $filters[] = array('wg_customer_config_activity.status', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllListBy($customerId) {

        $query = "SELECT
        wg_customer_config_job_activity.*, CONCAT(wg_customer_config_activity.`name`,' (',wg_customer_config_job_data.`name`, ')') `name`
    FROM
        `wg_customer_config_job_activity`
    INNER JOIN `wg_customer_config_job` ON `wg_customer_config_job_activity`.`job_id` = `wg_customer_config_job`.`id`
    INNER JOIN `wg_customer_config_job_data` ON `wg_customer_config_job`.`job_id` = `wg_customer_config_job_data`.`id`
    INNER JOIN `wg_customer_config_activity_process` ON `wg_customer_config_job_activity`.`activity_id` = `wg_customer_config_activity_process`.`id`
    INNER JOIN `wg_customer_config_activity` ON `wg_customer_config_activity_process`.`activity_id` = `wg_customer_config_activity`.`id`
    INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_job`.`workplace_id` = `wg_customer_config_workplace`.`id`
    AND `wg_customer_config_activity_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
    INNER JOIN `wg_customer_config_macro_process` ON `wg_customer_config_job`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
    AND `wg_customer_config_activity_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
    INNER JOIN `wg_customer_config_process` ON `wg_customer_config_job`.`process_id` = `wg_customer_config_process`.`id`
    AND `wg_customer_config_activity_process`.`process_id` = `wg_customer_config_process`.`id`
    LEFT JOIN `users` ON `wg_customer_config_job_activity`.`updatedby` = `users`.`id`
    WHERE
        wg_customer_config_activity.`status` = 'Activo'
    AND wg_customer_config_job.`status` = 'Activo'
    AND wg_customer_config_process.`status` = 'Activo'
    AND wg_customer_config_macro_process.`status` = 'Activo'
    AND wg_customer_config_workplace.`status` = 'Activo'
    AND `wg_customer_config_workplace`.customer_id = :customer_id
    ORDER BY
        wg_customer_config_activity.`name`";

        $results = DB::select( $query, array(
            "customer_id" => $customerId
        ));

        return $results;

    }
}
