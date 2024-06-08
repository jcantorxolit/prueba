<?php

namespace Wgroup\CustomerInvestigationAlDocument;

use DB;
use Exception;
use Log;
use Str;


class CustomerInvestigationAlDocumentService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDocumentRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationId = 0, $type = '') {

        $model = new CustomerInvestigationAlDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerInvestigationAlDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_document.id',
            'wg_customer_investigation_al_document.type',
            'wg_customer_investigation_al_document.description',
            'wg_customer_investigation_al_document.folio',
            'wg_customer_investigation_al_document.isActive'
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
                    $this->customerDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDocumentRepository->sortBy('wg_customer_investigation_al_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_document.customer_investigation_id', $customerInvestigationId);

        if ($type != '') {
            $filters[] = array('investigation_document_type.value', $type);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_document_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_document.description', $search);
            $filters[] = array('wg_customer_investigation_al_document.folio', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_document.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationId, $type)
    {

        $model = new CustomerInvestigationAlDocument();
        $this->customerDocumentRepository = new CustomerInvestigationAlDocumentRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_document.customer_investigation_id', $customerInvestigationId);

        if ($type != '') {
            $filters[] = array('investigation_document_type.value', $type);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_document_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_document.description', $search);
            $filters[] = array('wg_customer_investigation_al_document.folio', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_document.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, true, "");
    }

}
