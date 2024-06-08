<?php

namespace Wgroup\CustomerEmployeeValidity;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CustomerEmployeeValidityService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $quoteRepository;

    function __construct() {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "") {

        $model = new CustomerEmployeeValidity();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CustomerEmployeeValidityRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_employee_validity.id',
            'wg_customer_employee_validity.customer_employee_id',
            'wg_customer_employee_validity.startDate',
            'wg_customer_employee_validity.endDate',
            'wg_customer_employee_validity.description',
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
                    $this->quoteRepository->sortBy($colName, $dir);
                } else {
                    $this->quoteRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->quoteRepository->sortBy('wg_customer_employee_validity.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_employee_validity.startDate', $search);
            $filters[] = array('wg_customer_employee_validity.endDate', $search);
            $filters[] = array('wg_customer_employee_validity.description', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_employee_validity.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_employee_validity.isActive', '0');
        }


        $this->quoteRepository->setColumns(['wg_customer_employee_validity.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerEmployeeValidity();
        $this->quoteRepository = new CustomerEmployeeValidityRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_employee_validity.startDate', $search);
            $filters[] = array('wg_customer_employee_validity.endDate', $search);
            $filters[] = array('wg_customer_employee_validity.description', $search);
        }

        $this->quoteRepository->setColumns(['wg_customer_employee_validity.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }
}
