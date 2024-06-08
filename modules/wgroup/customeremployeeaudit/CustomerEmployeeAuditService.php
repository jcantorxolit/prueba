<?php

namespace Wgroup\CustomerEmployeeAudit;

use DB;
use Exception;
use Log;
use Str;

class CustomerEmployeeAuditService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerAuditRepository;

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

        $model = new CustomerEmployeeAudit();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerAuditRepository = new CustomerEmployeeAuditRepository($model);

        if ($perPage > 0) {
            $this->customerAuditRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_employee_audit.id',
            'wg_customer_employee_audit.model_name',
            'wg_customer_employee_audit.model_id',
            'wg_customer_employee_audit.user_type',
            'wg_customer_employee_audit.user_id',
            'wg_customer_employee_audit.observation',
            'wg_customer_employee_audit.action',
            'wg_customer_employee_audit.date'
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
                    $this->customerAuditRepository->sortBy($colName, $dir);
                } else {
                    $this->customerAuditRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerAuditRepository->sortBy('wg_customer_employee_audit.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee_audit.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_employee_audit.user_type', $search);
            $filters[] = array('wg_customer_employee_audit.model_name', $search);
            $filters[] = array('wg_customer_employee_audit.observation', $search);
            $filters[] = array('wg_customer_employee_audit.action', $search);
            $filters[] = array('wg_customer_employee_audit.date', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_employee_audit.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_employee_audit.status', '0');
        }


        $this->customerAuditRepository->setColumns(['wg_customer_employee_audit.*']);

        return $this->customerAuditRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllByCustomer($search, $perPage = 10, $currentPage = 0, $customerEmployeeId = 0, $audit = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.customer_employee_id, p.user_type, p.fullName, p.model_id, p.model_name, p.action, p.observation, p.date, p.userType
from (
select ca.*
		, CASE WHEN ca.user_type = 'system' THEN 'Sistema' WHEN ca.user_type = 'agent' THEN 'Asesor' WHEN ca.user_type = 'customer' THEN 'Cliente' ELSE ca.user_type END userType
		, CONCAT(u.email) fullName
from wg_customer_employee_audit ca
left join users u on u.id = ca.user_id
where ca.customer_employee_id = :customer_employee_id) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.date DESC";

        if ($audit != null) {
            $query.= $this->getWhere($audit->filters);
        }

        $query.=$orderBy.$limit;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return $results;

    }

    public function getAllByCustomerCount($search, $perPage = 10, $currentPage = 0, $customerEmployeeId = 0, $audit = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.customer_employee_id, p.user_type, p.fullName, p.model_id, p.model_name, p.action, p.observation, p.date, p.userType
from (
select ca.*
		, CASE WHEN ca.user_type = 'system' THEN 'Sistema' WHEN ca.user_type = 'agent' THEN 'Asesor' WHEN ca.user_type = 'customer' THEN 'Cliente' ELSE ca.user_type END userType
		, CONCAT(u.email) fullName
from wg_customer_employee_audit ca
left join users u on u.id = ca.user_id
where ca.customer_employee_id = :customer_employee_id) p ";

        $limit = " LIMIT $startFrom , $perPage";

        if ($audit != null) {
            $query.= $this->getWhere($audit->filters);
        }

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return $results;

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

    public function getCount($search = "", $customerEmployeeId) {

        $model = new CustomerEmployeeAudit();
        $this->customerAuditRepository = new CustomerEmployeeAuditRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_employee_audit.customer_employee_id', $customerEmployeeId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_employee_audit.user_type', $search);
            $filters[] = array('wg_customer_employee_audit.model_name', $search);
            $filters[] = array('wg_customer_employee_audit.observation', $search);
            $filters[] = array('wg_customer_employee_audit.action', $search);
            $filters[] = array('wg_customer_employee_audit.date', $search);
        }

        $this->customerAuditRepository->setColumns(['wg_customer_employee_audit.*']);

        return $this->customerAuditRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllAgentBy($sorting = array(), $customerEmployeeId) {

        $query = "	Select a.* from wg_agent a
                    inner join wg_customer_agent ca on a.id = ca.agent_id
                    where ca.customer_employee_id = :customer_employee_id
                    order by a.lastName";
        //Log::info($query);
        //Log::info($customerEmployeeId);
        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }
}
