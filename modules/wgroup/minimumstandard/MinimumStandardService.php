<?php

namespace Wgroup\MinimumStandard;

use DB;
use Exception;
use Log;
use Str;

class MinimumStandardService
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

        $model = new MinimumStandard();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new MinimumStandardRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_minimum_standard.id',
            'wg_minimum_standard.type',
            'wg_minimum_standard.parent_id',
            'wg_minimum_standard.cycle',
            'wg_minimum_standard.description',
            'wg_minimum_standard.numeral',
            'wg_minimum_standard.isActive'
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

                if ($colName == "wg_minimum_standard.id") {
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
            $this->repository->sortBy('wg_minimum_standard_parent.numeral', 'asc');
            $this->repository->addSortField('wg_minimum_standard.numeral', 'asc');
        } else {
            if (!array_key_exists("wg_minimum_standard_parent.numeral", $this->repository->getSortColumns())) {
                $this->repository->addSortField("wg_minimum_standard_parent.numeral", 'asc');
            }

            if (!array_key_exists("wg_minimum_standard.numeral", $this->repository->getSortColumns())) {
                $this->repository->addSortField("wg_minimum_standard.numeral", 'asc');
            }
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('minimum_standard_type.item', $search);
            $filters[] = array('minimum_standard_cycle.item', $search);
            $filters[] = array('wg_minimum_standard_parent.numeral', $search);
            $filters[] = array('wg_minimum_standard_parent.description', $search);
            $filters[] = array('wg_minimum_standard.numeral', $search);
            $filters[] = array('wg_minimum_standard.description', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_minimum_standard.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_minimum_standard.isActive', '0');
        }


        $this->repository->setColumns(['wg_minimum_standard.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new MinimumStandard();

        $this->repository = new MinimumStandardRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('minimum_standard_type.item', $search);
            $filters[] = array('minimum_standard_cycle.item', $search);
            $filters[] = array('wg_minimum_standard_parent.numeral', $search);
            $filters[] = array('wg_minimum_standard_parent.description', $search);
            $filters[] = array('wg_minimum_standard.numeral', $search);
            $filters[] = array('wg_minimum_standard.description', $search);
        }

        $this->repository->setColumns(['wg_minimum_standard.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
