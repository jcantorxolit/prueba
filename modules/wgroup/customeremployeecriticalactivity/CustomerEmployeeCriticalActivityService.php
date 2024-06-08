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

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0, $jobId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT
	*
FROM
	(
		SELECT
			eca.id,
			CONCAT(a.`name`,' (', jd.`name`,')') `fullname`,
			jd.`name` AS jobName,
            a.`name`,
			eca.customer_employee_id,
			eca.created_at
		FROM
			wg_customer_employee_critical_activity eca
        INNER JOIN wg_customer_config_job_activity ja ON eca.job_activity_id = ja.id
		INNER JOIN wg_customer_config_activity_process ap ON ja.activity_id = ap.id
		INNER JOIN wg_customer_config_activity a ON ap.activity_id = a.id
		INNER JOIN wg_customer_config_job j ON ja.job_id = j.id
		INNER JOIN wg_customer_config_job_data jd ON j.job_id = jd.id
		WHERE
			eca.customer_employee_id = :customer_employee_id
		AND eca.job_id = :job_id
	) p";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " WHERE (p.name like '%$search%') OR (p.jobName like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId,
            'job_id' => $jobId
        ));

        return $results;

    }

    public function getAllCount($search, $customerEmployeeId = 0, $jobId = 0) {

        $query = "SELECT
	*
FROM
	(
		SELECT
			eca.id,
			CONCAT(a.`name`,' (', jd.`name`,')') `fullname`,
            jd.`name` AS jobName,
            a.`name`,
			eca.customer_employee_id,
			eca.created_at
		FROM
			wg_customer_employee_critical_activity eca
        INNER JOIN wg_customer_config_job_activity ja ON eca.job_activity_id = ja.id
		INNER JOIN wg_customer_config_activity_process ap ON ja.activity_id = ap.id
		INNER JOIN wg_customer_config_activity a ON ap.activity_id = a.id
		INNER JOIN wg_customer_config_job j ON ja.job_id = j.id
		INNER JOIN wg_customer_config_job_data jd ON j.job_id = jd.id
		WHERE
			eca.customer_employee_id = :customer_employee_id
		AND eca.job_id = :job_id
	) p";

        if ($search != "") {
            $where = " WHERE (p.name like '%$search%') OR (p.jobName like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.created_at DESC ";

        $query.=$order;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId,
            'job_id' => $jobId
        ));

        return count($results);

    }

    public function insertJobActivityCritical($customerEmployeeId, $jobId)
    {
        $query = "INSERT INTO wg_customer_employee_critical_activity
SELECT DISTINCT
	NULL id,
	$customerEmployeeId customer_employee_id,
	ja.id job_activity_id,
	j.id jobId,
	1 createdBy,
	NULL updatedBy,
	NOW() created_at,
	NULL updated_at
FROM
	wg_customer_config_job_activity ja
INNER JOIN wg_customer_config_activity_process ap ON ja.activity_id = ap.id
INNER JOIN wg_customer_config_activity a ON ap.activity_id = a.id
INNER JOIN wg_customer_config_job j ON ja.job_id = j.id
LEFT JOIN (
	SELECT
		*
	FROM
		wg_customer_employee_critical_activity
	WHERE
		customer_employee_id = $customerEmployeeId
) cd ON cd.job_id = j.id
AND cd.job_activity_id = ja.id
WHERE
	j.id = $jobId
AND a.isCritical = 1
AND cd.id IS NULL";

        DB::statement( $query );
    }

    public static function bulkInsertJobActivityCritical()
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

    public function getAllCriticalActivity($customerEmployeeId, $jobId, $customerId) {


        $query = "SELECT
	ja.id,
	CONCAT(a.`name`, ' (', jd.`name`, ')') `name`,
	ja.job_id
FROM
	wg_customer_config_job_activity ja
INNER JOIN wg_customer_config_activity_process ap ON ja.activity_id = ap.id
INNER JOIN wg_customer_config_activity a ON ap.activity_id = a.id
INNER JOIN wg_customer_config_job j ON ja.job_id = j.id
INNER JOIN wg_customer_config_job_data jd ON j.job_id = jd.id
LEFT JOIN (
	SELECT
		*
	FROM
		wg_customer_employee_critical_activity
	WHERE
		customer_employee_id = $customerEmployeeId
) cd ON cd.job_id = j.id
AND cd.job_activity_id = ja.id
WHERE
	ja.id NOT IN (
		SELECT
			job_activity_id
		FROM
			wg_customer_employee_critical_activity
		WHERE
			customer_employee_id = $customerEmployeeId
		AND job_id = $jobId
	)
AND a.isCritical = 1 AND jd.customer_id = $customerId";

        $results = DB::select( $query );

        return $results;

    }
}
