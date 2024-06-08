<?php

namespace Wgroup\RoadSafetyItem;

use DB;
use Exception;
use Log;
use Str;

class RoadSafetyItemService
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
        $model = new RoadSafetyItem();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new RoadSafetyItemRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_road_safety_item.id',
            'wg_road_safety_item.road_safety_id',
            'wg_road_safety_item.road_safety_child_id',
            'wg_road_safety_item.numeral',
            'wg_road_safety_item.description',
            'wg_road_safety_item.value',
            'wg_road_safety_item.criterion',
            'wg_road_safety_item.isActive'
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

                if ($colName == "wg_road_safety_item.id") {
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
            $this->customerContractorRepository->sortBy('wg_road_safety_parent.numeral', 'asc');
            $this->customerContractorRepository->addSortField("wg_road_safety", "asc");
            $this->customerContractorRepository->addSortField("wg_road_safety_item", "asc");
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_road_safety.numeral', $search);
            $filters[] = array('wg_road_safety.description', $search);
            $filters[] = array('wg_road_safety_parent.numeral', $search);
            $filters[] = array('wg_road_safety_parent.description', $search);
            $filters[] = array('road_safety_cycle.item', $search);
            $filters[] = array('wg_road_safety_item.numeral', $search);
            $filters[] = array('wg_road_safety_item.description', $search);
            $filters[] = array('wg_road_safety_item.value', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_road_safety_item.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {
        $model = new RoadSafetyItem();
        $this->customerContractorRepository = new RoadSafetyItemRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_road_safety.numeral', $search);
            $filters[] = array('wg_road_safety.description', $search);
            $filters[] = array('wg_road_safety_parent.numeral', $search);
            $filters[] = array('wg_road_safety_parent.description', $search);
            $filters[] = array('road_safety_cycle.item', $search);
            $filters[] = array('wg_road_safety_item.numeral', $search);
            $filters[] = array('wg_road_safety_item.description', $search);
            $filters[] = array('wg_road_safety_item.value', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_road_safety_item.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
