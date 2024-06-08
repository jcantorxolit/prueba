<?php

namespace Wgroup\MinimumStandardItem;

use DB;
use Exception;
use Log;
use Str;

class MinimumStandardItemService
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
    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array())
    {
        $model = new MinimumStandardItem();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new MinimumStandardItemRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_minimum_standard_item.id',
            'wg_minimum_standard_item.minimum_standard_id',
            'wg_minimum_standard_item.minimum_standard_child_id',
            'wg_minimum_standard_item.numeral',
            'wg_minimum_standard_item.description',
            'wg_minimum_standard_item.value',
            'wg_minimum_standard_item.criterion',
            'wg_minimum_standard_item.isActive'
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

                if ($colName == "wg_minimum_standard_item.id") {
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
            $this->customerContractorRepository->sortBy('wg_minimum_standard_parent.numeral', 'asc');
            $this->customerContractorRepository->addSortField("wg_minimum_standard", "asc");
            $this->customerContractorRepository->addSortField("wg_minimum_standard_item", "asc");
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_minimum_standard.numeral', $search);
            $filters[] = array('wg_minimum_standard.description', $search);
            $filters[] = array('wg_minimum_standard_parent.numeral', $search);
            $filters[] = array('wg_minimum_standard_parent.description', $search);
            $filters[] = array('minimum_standard_cycle.item', $search);
            $filters[] = array('wg_minimum_standard_item.numeral', $search);
            $filters[] = array('wg_minimum_standard_item.description', $search);
            $filters[] = array('wg_minimum_standard_item.value', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_minimum_standard_item.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {
        $model = new MinimumStandardItem();
        $this->customerContractorRepository = new MinimumStandardItemRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_minimum_standard.numeral', $search);
            $filters[] = array('wg_minimum_standard.description', $search);
            $filters[] = array('wg_minimum_standard_parent.numeral', $search);
            $filters[] = array('wg_minimum_standard_parent.description', $search);
            $filters[] = array('minimum_standard_cycle.item', $search);
            $filters[] = array('wg_minimum_standard_item.numeral', $search);
            $filters[] = array('wg_minimum_standard_item.description', $search);
            $filters[] = array('wg_minimum_standard_item.value', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_minimum_standard_item.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
