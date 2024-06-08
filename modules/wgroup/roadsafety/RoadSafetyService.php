<?php

namespace Wgroup\RoadSafety;

use DB;
use Exception;
use Log;
use Str;

class RoadSafetyService
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
     * @param string $typeFilter
     * @return mixed
     */
    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new RoadSafety();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new RoadSafetyRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_road_safety.id',
            'wg_road_safety.type',
            'wg_road_safety.parent_id',
            'wg_road_safety.cycle',
            'wg_road_safety.description',
            'wg_road_safety.numeral',
            'wg_road_safety.isActive'
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

                if ($colName == "wg_road_safety.id") {
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
            $this->repository->sortBy('wg_road_safety_parent.numeral', 'asc');
            $this->repository->addSortField('wg_road_safety.numeral', 'asc');
        } else {
            if (!array_key_exists("wg_road_safety_parent.numeral", $this->repository->getSortColumns())) {
                $this->repository->addSortField("wg_road_safety_parent.numeral", 'asc');
            }

            if (!array_key_exists("wg_road_safety.numeral", $this->repository->getSortColumns())) {
                $this->repository->addSortField("wg_road_safety.numeral", 'asc');
            }
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('road_safety_type.item', $search);
            $filters[] = array('road_safety_cycle.item', $search);
            $filters[] = array('wg_road_safety_parent.numeral', $search);
            $filters[] = array('wg_road_safety_parent.description', $search);
            $filters[] = array('wg_road_safety.numeral', $search);
            $filters[] = array('wg_road_safety.description', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_road_safety.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_road_safety.isActive', '0');
        }


        $this->repository->setColumns(['wg_road_safety.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new RoadSafety();

        $this->repository = new RoadSafetyRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('road_safety_type.item', $search);
            $filters[] = array('road_safety_cycle.item', $search);
            $filters[] = array('wg_road_safety_parent.numeral', $search);
            $filters[] = array('wg_road_safety_parent.description', $search);
            $filters[] = array('wg_road_safety.numeral', $search);
            $filters[] = array('wg_road_safety.description', $search);
        }

        $this->repository->setColumns(['wg_road_safety.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
