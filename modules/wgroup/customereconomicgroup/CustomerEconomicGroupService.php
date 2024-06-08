<?php

namespace Wgroup\CustomerEconomicGroup;

use DB;
use Exception;
use Log;
use Str;

class CustomerEconomicGroupService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

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

        $model = new CustomerEconomicGroup();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerEconomicGroupRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_economic_group.id',
            'wg_customer_economic_group.type',
            'wg_customer_economic_group.cause',
            'wg_customer_economic_group.firstName',
            'wg_customer_economic_group.lastName',
            'wg_customer_economic_group.start',
            'wg_customer_economic_group.end'
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
                    $this->customerContractorRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContractorRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContractorRepository->sortBy('wg_customer_economic_group.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_economic_group.parent_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_economic_group.id', $search);
            $filters[] = array('wg_customer_economic_group.parent_id', $search);
            $filters[] = array('wg_customer_economic_group.customer_id', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }


        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_economic_group.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_economic_group.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_economic_group.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerEconomicGroup();
        $this->customerContractorRepository = new CustomerEconomicGroupRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_economic_group.parent_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_economic_group.id', $search);
            $filters[] = array('wg_customer_economic_group.parent_id', $search);
            $filters[] = array('wg_customer_economic_group.customer_id', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_economic_group.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllForEconomicGroup($customerId)
    {
        $query = "Select id, documentNumber, businessName from wg_customers
where id not in ( select customer_id from wg_customer_economic_group ) and (isDeleted = 0 or isDeleted is null) and `status` = '1' and id <> :customer_id";

        $results = DB::select( $query, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllCustomerWithEconomicGroup($agentId = 0)
    {
        $query = "SELECT * FROM (
Select id, documentNumber, businessName from wg_customers
where hasEconomicGroup = 1  and (isDeleted = 0 or isDeleted is null) and `status` = '1'
ORDER BY businessName) p";

        $where = '';

        if ($agentId != '' && $agentId != '0') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_id FROM `wg_customer_agent` WHERE `wg_customer_agent`.`agent_id` = '$agentId')";
        }

        $sql= $query.$where;

        $results = DB::select( $sql );

        return $results;
    }

    public function getAllCustomerWithoutEconomicGroup($agentId = 0)
    {
        $query = "SELECT * FROM (
        SELECT c.id,
       c.documentNumber,
       c.businessName,
       p.item arl
FROM wg_customers c
LEFT JOIN
  (SELECT * FROM system_parameters WHERE system_parameters.group = 'arl') p ON c.arl = p.value
WHERE c.id NOT IN (SELECT customer_id FROM wg_customer_economic_group)
  AND (isDeleted = 0 OR isDeleted IS NULL)
  AND `status` = '1'
ORDER BY c.businessName) p";

        $where = '';

        if ($agentId != '' && $agentId != '0') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_id FROM `wg_customer_agent` WHERE `wg_customer_agent`.`agent_id` = '$agentId')";
        }

        $sql= $query.$where;

        $results = DB::select( $sql );

        return $results;
    }

    public function getAllTaskType()
    {
        $query = "select id, `code`, description, price
from wg_project_task_type
where isActive = 1
order by description";

        $results = DB::select( $query );

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
}
