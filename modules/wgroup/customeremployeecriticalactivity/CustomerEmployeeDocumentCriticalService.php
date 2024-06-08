<?php

namespace Wgroup\CustomerEmployeeCriticalActivity;

use DB;
use Exception;
use Log;
use Str;

class CustomerEmployeeCriticalActivityService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerEmployeeDocumentRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0) {

        $model = new CustomerEmployeeCriticalActivity();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerEmployeeDocumentRepository = new CustomerEmployeeCriticalActivityRepository($model);

        if ($perPage > 0) {
            $this->customerEmployeeDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_employee_critical_activity.id',
            'wg_customer_employee_critical_activity.job_activity_id',
            'wg_customer_employee_critical_activity.job_id',
            'wg_customer_employee_critical_activity.customer_employee_id',
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
                    $this->customerEmployeeDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerEmployeeDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerEmployeeDocumentRepository->sortBy('wg_customer_employee_critical_activity.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee_critical_activity.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_employee_critical_activity.requirement', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_employee_critical_activity.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_employee_critical_activity.status', '0');
        }


        $this->customerEmployeeDocumentRepository->setColumns(['wg_customer_employee_critical_activity.*']);

        return $this->customerEmployeeDocumentRepository->getFilteredsOptional($filters, false, "");
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

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE ".$where;
    }



    public function insertJobActivityCritical($jobId, $customerEmployeeId)
    {
        $query = "insert into wg_customer_employee_critical_activity
select DISTINCT
	null id, $customerEmployeeId customer_employee_id, ja.id job_activity_id, j.id jobId, 1 createdBy, null updatedBy, NOW() created_at, null updated_at
from wg_customer_config_job_activity ja
inner join wg_customer_config_job j on ja.job_id = j.id
left join (select * from wg_customer_employee_critical_activity where customer_employee_id = $customerEmployeeId)  cd on cd.job_id = j.id and cd.job_activity_id = ja.id
where j.id = $jobId and ja.isCritical = 1 and cd.id is null";

        DB::statement( $query );
    }

    public function getAllCriticalActivity($jobId, $customerEmployeeId) {


        $query = "select
	ja.*
from wg_customer_config_job_activity ja
inner join wg_customer_config_job j on ja.job_id = j.id
left join (select * from wg_customer_employee_critical_activity where customer_employee_id = $customerEmployeeId)  cd on cd.job_id = j.id and cd.job_activity_id = ja.id
where ja.id not in (select job_activity_id from wg_customer_employee_critical_activity where customer_employee_id = $customerEmployeeId and job_id = $jobId)
and ja.isCritical = 1";

        $results = DB::select( $query );

        return $results;

    }
}
