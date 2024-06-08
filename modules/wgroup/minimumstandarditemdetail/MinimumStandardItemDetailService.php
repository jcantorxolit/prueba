<?php

namespace Wgroup\MinimumStandardItemDetail;

use DB;
use Exception;
use Log;
use Str;

class MinimumStandardItemDetailService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $minimumStandardItemId = 0)
    {

        $model = new MinimumStandardItemDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new MinimumStandardItemDetailRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_minimum_standard_item_detail.id',
            'wg_minimum_standard_item_detail.minimum_standard_item_id',
            'wg_minimum_standard_item_detail.type',
            'wg_minimum_standard_item_detail.description'
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
            $this->customerContractorRepository->sortBy('wg_minimum_standard_item_detail.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_minimum_standard_item_detail.minimum_standard_item_id', $minimumStandardItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_minimum_standard_item_detail.type', $search);
            $filters[] = array('wg_minimum_standard_item_detail.description', $search);
            $filters[] = array('wg_minimum_standard_item.numeral', $search);
            $filters[] = array('wg_minimum_standard_item.description', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_minimum_standard_item_detail.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $minimumStandardItemId)
    {

        $model = new MinimumStandardItemDetail();
        $this->customerContractorRepository = new MinimumStandardItemDetailRepository($model);

        $filters = array();

        $filters[] = array('wg_minimum_standard_item_detail.minimum_standard_item_id', $minimumStandardItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_minimum_standard_item_detail.type', $search);
            $filters[] = array('wg_minimum_standard_item_detail.description', $search);
            $filters[] = array('wg_minimum_standard_item.numeral', $search);
            $filters[] = array('wg_minimum_standard_item.description', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_minimum_standard_item_detail.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
