<?php

namespace Wgroup\CustomerInvestigationAlFactor;

use DB;
use Exception;
use Log;
use Str;

class CustomerInvestigationAlFactorService
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
     * @param string $customerInvestigationId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationId = 0)
    {

        $model = new CustomerInvestigationAlFactor();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerInvestigationAlFactorRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_factor.id',
            'wg_customer_investigation_al_factor.factor',
            'wg_customer_investigation_al_factor.cause',
            'wg_customer_investigation_al_factor.sort',
            'wg_customer_investigation_al_factor.customer_investigation_id',
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
            $this->customerContractorRepository->sortBy('wg_customer_investigation_al_factor.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_factor.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_factor.item', $search);
            $filters[] = array('wg_customer_investigation_al_factor.cause', $search);
            $filters[] = array('wg_customer_investigation_al_factor.sort', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_investigation_al_factor.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationId)
    {

        $model = new CustomerInvestigationAlFactor();
        $this->customerContractorRepository = new CustomerInvestigationAlFactorRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_factor.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_factor.item', $search);
            $filters[] = array('wg_customer_investigation_al_factor.cause', $search);
            $filters[] = array('wg_customer_investigation_al_factor.sort', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_investigation_al_factor.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getFactors($investigationId)
    {
        $sql = "SELECT factor id,
        UPPER(p.item) `name`
FROM `wg_customer_investigation_al_factor` f
INNER JOIN
  (SELECT *
   FROM system_parameters
   WHERE `group` = 'investigation_factor'
     AND namespace = 'wgroup') p ON f.factor = p.`value` COLLATE utf8_general_ci
WHERE customer_investigation_id = :customer_investigation_id
GROUP BY p.item;";

        $results = DB::select($sql, array(
            'customer_investigation_id' => $investigationId
        ));

        return $results;
    }

    public function getCauses($investigationId)
    {
        $sql = "SELECT factor,
       cause `name`
FROM `wg_customer_investigation_al_factor` f
WHERE customer_investigation_id = :customer_investigation_id
ORDER BY `sort`;";

        $results = DB::select($sql, array(
            'customer_investigation_id' => $investigationId
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
