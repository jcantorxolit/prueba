<?php

namespace Wgroup\CustomerParameter;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CustomerParameterService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId, $namespace) {

        $model = new CustomerParameter();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CustomerParameterRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_parameter.id',
            'wg_customer_parameter.customer_id',
            'wg_customer_parameter.namespace',
            'wg_customer_parameter.group',
            'wg_customer_parameter.item',
            'wg_customer_parameter.value',
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
            $this->quoteRepository->sortBy('wg_customer_parameter.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_parameter.customer_id', $customerId);
        $filters[] = array('wg_customer_parameter.group', $namespace);
        $filters[] = array('wg_customer_parameter.item', 1);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_parameter.value', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_parameter.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_parameter.isActive', '0');
        }


        $this->quoteRepository->setColumns(['wg_customer_parameter.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId, $namespace) {

        $model = new CustomerParameter();
        $this->quoteRepository = new CustomerParameterRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_parameter.customer_id', $customerId);
        $filters[] = array('wg_customer_parameter.group', $namespace);
        $filters[] = array('wg_customer_parameter.item', 1);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_parameter.value', $search);
        }

        $this->quoteRepository->setColumns(['wg_customer_parameter.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }
}
