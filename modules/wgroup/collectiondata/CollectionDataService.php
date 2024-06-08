<?php

namespace Wgroup\CollectionData;

use DB;
use Exception;
use Log;
use Str;

class CollectionDataService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $collectionDataRepository;

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
    public function getAllBy($search, $perPage = 1000000, $currentPage = 0, $sorting = array(), $module = "customer") {

        $model = new CollectionData();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->collectionDataRepository = new CollectionDataRepository($model);

        if ($perPage > 0) {
            $this->collectionDataRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_collection_data.id',
            'wg_collection_data.name',
            'wg_collection_data.viewName',
            'wg_collection_data.type',
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
                    $this->collectionDataRepository->sortBy($colName, $dir);
                } else {
                    $this->collectionDataRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->collectionDataRepository->sortBy('wg_collection_data.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_collection_data.customer_id', $customerId);

        $filters[] = array('wg_collection_data.module', $module);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_collection_data.isActive', $search);
        }


        $this->collectionDataRepository->setColumns(['wg_collection_data.*']);

        return $this->collectionDataRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $module = "customer") {

        $model = new CollectionData();
        $this->collectionDataRepository = new CollectionDataRepository($model);

        $filters = array();

        $filters[] = array('wg_collection_data.module', $module);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_collection_data.id', $search);
            $filters[] = array('wg_collection_data.name', $search);
            $filters[] = array('wg_collection_data.viewName', $search);
            $filters[] = array('wg_collection_data.type', $search);
        }

        $this->collectionDataRepository->setColumns(['wg_collection_data.*']);

        return $this->collectionDataRepository->getFilteredsOptional($filters, true, "");
    }

}
