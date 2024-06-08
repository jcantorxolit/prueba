<?php

namespace Wgroup\Budget;

use DB;
use Exception;
use Log;
use Str;

class BudgetService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
    }

    public function init()
    {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array())
    {

        $model = new Budget();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new BudgetRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_budget.id',
            'wg_budget.item',
            'wg_budget.description',
            'wg_budget.classification',
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
            $this->customerContractorRepository->sortBy('wg_budget.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_budget.id', $search);
            $filters[] = array('wg_budget.item', $search);
            $filters[] = array('wg_budget.description', $search);
            $filters[] = array('wg_budget.classification', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_budget.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new Budget();
        $this->customerContractorRepository = new BudgetRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_budget.id', $search);
            $filters[] = array('wg_budget.item', $search);
            $filters[] = array('wg_budget.description', $search);
            $filters[] = array('wg_budget.classification', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_budget.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(
Select
	wg_budget.id,
	wg_budget.item,
	wg_budget.description,
	project_type.item classification,
	detail.amount,
	detail.`year`
from wg_budget
left join (select * from system_parameters where `group` = 'project_type') project_type on project_type.value = wg_budget.classification
left join (select
                budget_id, `year`, SUM(amount) amount
            from wg_budget_detail
            group by `year`, budget_id) detail on detail.budget_id = wg_budget.id
) p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.year DESC ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.item like '%$search%' or p.description like '%$search%' or p.classification like '%$search%' or p.year like '%$search%')";
        }

        $sql = $query.$where.$orderBy;
        $sql .= $limit;

        $results = DB::select( $sql );

        return $results;
    }

    public function getAllCountBy($search, $filter = null) {

        $query = "SELECT * FROM
(
Select
	wg_budget.id,
	wg_budget.item,
	wg_budget.description,
	project_type.item classification,
	detail.amount,
	detail.`year`
from wg_budget
left join (select * from system_parameters where `group` = 'project_type') project_type on project_type.value = wg_budget.classification
left join (select
                budget_id, `year`, SUM(amount) amount
            from wg_budget_detail
            group by `year`, budget_id) detail on detail.budget_id = wg_budget.id
) p";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.item like '%$search%' or p.description like '%$search%' or p.classification like '%$search%' or p.year like '%$search%')";
        }

        $sql = $query.$where;

        $results = DB::select( $sql );

        return count($results);
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE " . $where;
    }
}
