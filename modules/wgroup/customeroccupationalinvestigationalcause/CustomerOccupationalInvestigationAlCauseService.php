<?php

namespace Wgroup\CustomerOccupationalInvestigationAlCause;

use DB;
use Exception;
use Log;
use Str;

class CustomerOccupationalInvestigationAlCauseService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationId = 0, $type = 'immediate')
    {

        $model = new CustomerOccupationalInvestigationAlCause();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerOccupationalInvestigationAlCauseRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_occupational_investigation_al_cause.id',
            'wg_customer_occupational_investigation_al_cause.type',
            'wg_customer_occupational_investigation_al_cause.cause',
            'wg_customer_occupational_investigation_al_cause.factor',
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
            $this->customerContractorRepository->sortBy('wg_customer_occupational_investigation_al_cause.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_occupational_investigation_al_cause.customer_occupational_investigation_id', $customerInvestigationId);
        $filters[] = array('wg_customer_occupational_investigation_al_cause.type', $type);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_testimony_type.item', $search);
            $filters[] = array('investigation_cause_classification_immediate.item', $search);
            $filters[] = array('investigation_cause_classification_basic.item', $search);
            $filters[] = array('wg_investigation_cause.code', $search);
            $filters[] = array('wg_investigation_cause.name', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_occupational_investigation_al_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationId, $type = 'immediate')
    {
        $model = new CustomerOccupationalInvestigationAlCause();
        $this->customerContractorRepository = new CustomerOccupationalInvestigationAlCauseRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_occupational_investigation_al_cause.customer_occupational_investigation_id', $customerInvestigationId);
        $filters[] = array('wg_customer_occupational_investigation_al_cause.type', $type);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_testimony_type.item', $search);
            $filters[] = array('investigation_cause_classification_immediate.item', $search);
            $filters[] = array('investigation_cause_classification_basic.item', $search);
            $filters[] = array('wg_investigation_cause.code', $search);
            $filters[] = array('wg_investigation_cause.name', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_occupational_investigation_al_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
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
