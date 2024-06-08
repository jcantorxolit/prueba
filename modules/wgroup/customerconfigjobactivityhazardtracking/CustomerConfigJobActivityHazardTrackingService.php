<?php

namespace Wgroup\CustomerConfigJobActivityHazardTracking;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigJobActivityHazardTrackingService {

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

        $model = new CustomerConfigJobActivityHazardTracking();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityHazardTrackingRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_job_activity_hazard_tracking.type',
            'wg_customer_config_job_activity_hazard_tracking.description',
            'wg_customer_config_job_activity_hazard_tracking.item',
            'wg_customer_config_job_activity_hazard_tracking.source',
            'wg_customer_config_job_activity_hazard_tracking.created_at'
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
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_config_job_activity_hazard_tracking.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity_hazard_tracking.job_activity_hazard_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job_activity_hazard_tracking.type', $search);
            $filters[] = array('wg_customer_config_job_activity_hazard_tracking.description', $search);
            $filters[] = array('wg_customer_config_job_activity_hazard_tracking.item', $search);
            $filters[] = array('wg_customer_config_job_activity_hazard_tracking.source', $search);
            $filters[] = array('wg_customer_config_job_activity_hazard_tracking.created_at', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity_hazard_tracking.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $jobId) {

        $model = new CustomerConfigJobActivityHazardTracking();
        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityHazardTrackingRepository($model);

        $filters = array();

		$filters[] = array('wg_customer_config_job_activity_hazard_tracking.job_activity_hazard_id', $jobId);

		if (strlen(trim($search)) > 0) {
			$filters[] = array('wg_customer_config_job_activity_hazard_tracking.type', $search);
			$filters[] = array('wg_customer_config_job_activity_hazard_tracking.description', $search);
			$filters[] = array('wg_customer_config_job_activity_hazard_tracking.item', $search);
			$filters[] = array('wg_customer_config_job_activity_hazard_tracking.source', $search);
			$filters[] = array('wg_customer_config_job_activity_hazard_tracking.created_at', $search);
		}

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity_hazard_tracking.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter  == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        return $where == "" ? "" : " WHERE ".$where;
    }

}
