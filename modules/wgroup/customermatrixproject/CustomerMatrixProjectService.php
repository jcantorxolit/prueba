<?php

namespace Wgroup\CustomerMatrixProject;

use DB;
use Exception;
use Log;
use Str;


class CustomerMatrixProjectService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerRepository();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerMatrixId)
    {

        $model = new CustomerMatrixProject();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerMatrixProjectRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_matrix_project.name',
            'wg_customer_matrix_project.isActive'
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
                    $this->customerConfigWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_matrix_project.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_matrix_project.customer_matrix_id', $customerMatrixId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_matrix_project.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_matrix_project.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerMatrixId)
    {

        $model = new CustomerMatrixProject();
        $this->customerConfigWorkPlaceRepository = new CustomerMatrixProjectRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_matrix_project.customer_matrix_id', $customerMatrixId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_matrix_project.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_matrix_project.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }
}
