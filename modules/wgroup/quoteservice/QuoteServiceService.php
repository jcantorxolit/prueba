<?php

namespace Wgroup\QuoteService;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerDocument;
use Wgroup\Models\CustomerDocumentReporistory;

class QuoteServiceService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $quoteServiceRepository;

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

        $model = new QuoteService();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteServiceRepository = new QuoteServiceRepository($model);

        if ($perPage > 0) {
            $this->quoteServiceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_quote_service.id',
            'wg_quote_service.name',
            'wg_quote_service.hour',
            'wg_quote_service.unitValue',
            'wg_quote_service.unitMeasure',
            'wg_quote_service.isActive'
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
                    $this->quoteServiceRepository->sortBy($colName, $dir);
                } else {
                    $this->quoteServiceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->quoteServiceRepository->sortBy('wg_quote_service.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_quote_service.name', $search);
            $filters[] = array('wg_quote_service.hour', $search);
            $filters[] = array('wg_quote_service.unitValue', $search);
            $filters[] = array('wg_quote_service.unitMeasure', $search);
            $filters[] = array('wg_quote_service.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_quote_service.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_quote_service.isActive', '0');
        }


        $this->quoteServiceRepository->setColumns(['wg_quote_service.*']);

        return $this->quoteServiceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new QuoteService();
        $this->quoteServiceRepository = new QuoteServiceRepository($model);

        $filters = array();

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_quote_service.name', $search);
            $filters[] = array('wg_quote_service.hour', $search);
            $filters[] = array('wg_quote_service.unitValue', $search);
            $filters[] = array('wg_quote_service.unitMeasure', $search);
            $filters[] = array('wg_quote_service.isActive', $search);
        }

        $this->quoteServiceRepository->setColumns(['wg_quote_service.*']);

        return $this->quoteServiceRepository->getFilteredsOptional($filters, true, "");
    }
}
