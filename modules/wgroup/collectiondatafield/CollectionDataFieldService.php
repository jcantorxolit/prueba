<?php

namespace Wgroup\CollectionDataField;

use DB;
use Exception;
use Log;
use Str;

class CollectionDataFieldService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "") {

        $model = new CollectionDataField();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->collectionDataRepository = new CollectionDataFieldRepository($model);

        if ($perPage > 0) {
            $this->collectionDataRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_collection_data_field.id',
            'wg_collection_data_field.name',
            'wg_collection_data_field.table',
            'wg_collection_data_field.alias',
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
            $this->collectionDataRepository->sortBy('wg_collection_data_field.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_collection_data_field.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_collection_data_field.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_collection_data_field.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_collection_data_field.isActive', '0');
        }


        $this->collectionDataRepository->setColumns(['wg_collection_data_field.*']);

        return $this->collectionDataRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CollectionDataField();
        $this->collectionDataRepository = new CollectionDataFieldRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_collection_data_field.id', $search);
            $filters[] = array('wg_collection_data_field.name', $search);
            $filters[] = array('wg_collection_data_field.table', $search);
            $filters[] = array('wg_collection_data_field.alias', $search);
        }

        $this->collectionDataRepository->setColumns(['wg_collection_data_field.*']);

        return $this->collectionDataRepository->getFilteredsOptional($filters, true, "");
    }

}
