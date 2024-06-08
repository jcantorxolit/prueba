<?php

namespace Wgroup\CustomerAbsenteeismDisabilityReportAL;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerAbsenteeismDisabilityReportAL;
use Wgroup\Models\CustomerAbsenteeismDisabilityReportALRepository;

class CustomerAbsenteeismDisabilityReportALService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDocumentRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new CustomerAbsenteeismDisabilityReportAL();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerAbsenteeismDisabilityReportALRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_absenteeism_disability_report_al.id',
            'wg_customer_absenteeism_disability_report_al.type',
            'wg_customer_absenteeism_disability_report_al.classification',
            'wg_customer_absenteeism_disability_report_al.description',
            'wg_customer_absenteeism_disability_report_al.version',
            'wg_customer_absenteeism_disability_report_al.agent_id',
            'wg_customer_absenteeism_disability_report_al.status'
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
                    $this->customerDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDocumentRepository->sortBy('wg_customer_absenteeism_disability_report_al.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_disability_report_al.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_absenteeism_disability_report_al.type', $search);
            $filters[] = array('wg_customer_absenteeism_disability_report_al.classification', $search);
            $filters[] = array('wg_customer_absenteeism_disability_report_al.description', $search);
            $filters[] = array('wg_customer_absenteeism_disability_report_al.version', $search);
            $filters[] = array('wg_customer_absenteeism_disability_report_al.agent_id', $search);
            $filters[] = array('wg_customer_absenteeism_disability_report_al.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_absenteeism_disability_report_al.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_absenteeism_disability_report_al.status', '0');
        }


        $this->customerDocumentRepository->setColumns(['wg_customer_absenteeism_disability_report_al.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }


    public function getAllById($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerDisabilityId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select d.id, r.accident_date, r.accident_description, r.status from wg_customer_occupational_report_al r
INNER JOIN wg_customer_absenteeism_disability_report_al d on d.customer_occupational_report_al_id = r.id
WHERE d.customer_disability_id = :customer_disability_id";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (r.status like '%$search%' or r.accident_description like '%$search%' or r.accident_date like '%$search%')";
            $query.=$where;
        }

        $order = " Order by d.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_disability_id' => $customerDisabilityId
        ));

        return $results;

    }

    public function getCount($search = "", $customerDisabilityId) {

        $query = "select r.* from wg_customer_occupational_report_al r
INNER JOIN wg_customer_absenteeism_disability_report_al d on d.customer_occupational_report_al_id = r.id
WHERE d.customer_disability_id = :customer_disability_id";

        if ($search != "") {
            $where = " AND (r.status like '%$search%' or r.accident_description like '%$search%' or r.accident_date like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'customer_disability_id' => $customerDisabilityId
        ));

        return count($results);
    }

    public function getAllByAvailable($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerDisabilityId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select DISTINCT r.* from wg_customer_occupational_report_al r
INNER JOIN (
select d.* from wg_customer_absenteeism_disability d
inner join wg_customer_employee ce on d.customer_employee_id = ce.id
inner join wg_employee e on ce.employee_id = e.id
WHERE d.id = :customer_disability_id
) d on d.customer_employee_id = r.customer_employee_id
where r.id not in (SELECT customer_occupational_report_al_id from wg_customer_absenteeism_disability_report_al)";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (r.status like '%$search%' or r.accident_description like '%$search%' or r.accident_date like '%$search%')";
            $query.=$where;
        }

        $order = " Order by d.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
           'customer_disability_id' => $customerDisabilityId
        ));

        return $results;

    }

    public function getAvailableCount($search = "", $customerDisabilityId) {

        $query = "select DISTINCT r.* from wg_customer_occupational_report_al r
INNER JOIN (
select d.* from wg_customer_absenteeism_disability d
inner join wg_customer_employee ce on d.customer_employee_id = ce.id
inner join wg_employee e on ce.employee_id = e.id
WHERE d.id = :customer_disability_id
) d on d.customer_employee_id = r.customer_employee_id
where r.id not in (SELECT customer_occupational_report_al_id from wg_customer_absenteeism_disability_report_al)";

        if ($search != "") {
            $where = " AND (r.status like '%$search%' or r.accident_description like '%$search%' or r.accident_date like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'customer_disability_id' => $customerDisabilityId
        ));

        return count($results);
    }
}
