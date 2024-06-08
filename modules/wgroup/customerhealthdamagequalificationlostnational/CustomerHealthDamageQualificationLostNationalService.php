<?php

namespace Wgroup\CustomerHealthDamageQualificationLostNational;

use DB;
use Exception;
use Log;
use Str;

class CustomerHealthDamageQualificationLostNationalService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerHealthDamageId = 0)
    {

        $model = new CustomerHealthDamageQualificationLostNational();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerHealthDamageQualificationLostNationalRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_health_damage_ql_second_instance.id',
            'wg_customer_health_damage_ql_second_instance.controversyStatus',
            'wg_customer_health_damage_ql_second_instance.structuringDate',
            'wg_customer_health_damage_ql_second_instance.dateOf',
            'wg_customer_health_damage_ql_second_instance.notificationDate',
            'wg_customer_health_damage_ql_second_instance.origin',
            'wg_customer_health_damage_ql_second_instance.percentageRating',
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
            $this->customerContractorRepository->sortBy('wg_customer_health_damage_ql_second_instance.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id', $customerHealthDamageId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_health_damage_ql_second_instance.id', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.controversyStatus', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.structuringDate', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.dateOf', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.notificationDate', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.origin', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.percentageRating', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_health_damage_ql_second_instance.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerHealthDamageId)
    {

        $model = new CustomerHealthDamageQualificationLostNational();
        $this->customerContractorRepository = new CustomerHealthDamageQualificationLostNationalRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id', $customerHealthDamageId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_health_damage_ql_second_instance.id', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.controversyStatus', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.structuringDate', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.dateOf', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.notificationDate', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.origin', $search);
            $filters[] = array('wg_customer_health_damage_ql_second_instance.percentageRating', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_health_damage_ql_second_instance.*']);

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
