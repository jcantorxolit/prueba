<?php

namespace Wgroup\CustomerInvestigationAlCause;

use DB;
use Exception;
use Log;
use Str;

class CustomerInvestigationAlCauseService
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

        $model = new CustomerInvestigationAlCause();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerInvestigationAlCauseRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_cause.id',
            'wg_customer_investigation_al_cause.insecureAct',
            'wg_customer_investigation_al_cause.insecureActObservation',
            'wg_customer_investigation_al_cause.insecureCondition',
            'wg_customer_investigation_al_cause.insecureConditionObservation',
            'wg_customer_investigation_al_cause.workFactor',
            'wg_customer_investigation_al_cause.workFactorObservation',
            'wg_customer_investigation_al_cause.personalFactor',
            'wg_customer_investigation_al_cause.personalFactorObservation',
            'wg_customer_investigation_al_cause.observation',
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
            $this->customerContractorRepository->sortBy('wg_customer_investigation_al_cause.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_cause.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_investigation_al_cause.workFactorObservation', $search);
            $filters[] = array('wg_customer_investigation_al_cause.personalFactor', $search);
            $filters[] = array('wg_customer_investigation_al_cause.personalFactorObservation', $search);
            $filters[] = array('wg_customer_investigation_al_cause.observation', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_investigation_al_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationId)
    {

        $model = new CustomerInvestigationAlCause();
        $this->customerContractorRepository = new CustomerInvestigationAlCauseRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_cause.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_investigation_al_cause.workFactorObservation', $search);
            $filters[] = array('wg_customer_investigation_al_cause.personalFactor', $search);
            $filters[] = array('wg_customer_investigation_al_cause.personalFactorObservation', $search);
            $filters[] = array('wg_customer_investigation_al_cause.observation', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_investigation_al_cause.*']);

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
