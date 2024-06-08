<?php

namespace Wgroup\CustomerUnsafeActObservation;

use DB;
use Exception;
use Log;
use Str;

class CustomerUnsafeActObservationService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

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
     * @param int $customerId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerId = 0)
    {

        $model = new CustomerUnsafeActObservation();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerUnsafeActObservationRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_unsafe_act_observation.id',
            'wg_customer_unsafe_act_observation.dateOf',
            'wg_customer_unsafe_act_observation.description',
            'customer_unsafe_act_status.item'
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
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_customer_unsafe_act_observation.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_unsafe_act_observation.customer_unsafe_act_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_unsafe_act_observation.dateOf', $search);
            $filters[] = array('wg_customer_unsafe_act_observation.description', $search);
            $filters[] = array('customer_unsafe_act_status.item', $search);
        }

        $this->repository->setColumns(['wg_customer_unsafe_act_observation.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerUnsafeActObservation();
        $this->repository = new CustomerUnsafeActObservationRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_unsafe_act_observation.customer_unsafe_act_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_unsafe_act_observation.dateOf', $search);
            $filters[] = array('wg_customer_unsafe_act_observation.description', $search);
            $filters[] = array('customer_unsafe_act_status.item', $search);
        }

        $this->repository->setColumns(['wg_customer_unsafe_act_observation.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
