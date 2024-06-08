<?php

namespace Wgroup\RoadSafetyItemDetail;

use DB;
use Exception;
use Log;
use Str;

class RoadSafetyItemDetailService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $roadSafetyItemId = 0)
    {

        $model = new RoadSafetyItemDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new RoadSafetyItemDetailRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_road_safety_item_detail.id',
            'wg_road_safety_item_detail.road_safety_item_id',
            'wg_road_safety_item_detail.type',
            'wg_road_safety_item_detail.description'
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
            $this->customerContractorRepository->sortBy('wg_road_safety_item_detail.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_road_safety_item_detail.road_safety_item_id', $roadSafetyItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_road_safety_item_detail.type', $search);
            $filters[] = array('wg_road_safety_item_detail.description', $search);
            $filters[] = array('wg_road_safety_item.numeral', $search);
            $filters[] = array('wg_road_safety_item.description', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_road_safety_item_detail.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $roadSafetyItemId)
    {

        $model = new RoadSafetyItemDetail();
        $this->customerContractorRepository = new RoadSafetyItemDetailRepository($model);

        $filters = array();

        $filters[] = array('wg_road_safety_item_detail.road_safety_item_id', $roadSafetyItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_road_safety_item_detail.type', $search);
            $filters[] = array('wg_road_safety_item_detail.description', $search);
            $filters[] = array('wg_road_safety_item.numeral', $search);
            $filters[] = array('wg_road_safety_item.description', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_road_safety_item_detail.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
