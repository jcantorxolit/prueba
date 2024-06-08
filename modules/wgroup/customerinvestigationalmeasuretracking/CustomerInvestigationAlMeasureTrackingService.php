<?php

namespace Wgroup\CustomerInvestigationAlMeasureTracking;

use DB;
use Exception;
use Log;
use Str;


class CustomerInvestigationAlMeasureTrackingService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationMeasureId = 0) {

        $model = new CustomerInvestigationAlMeasureTracking();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerInvestigationAlMeasureTrackingRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_measure_tracking.id',
            'wg_customer_investigation_al_measure_tracking.type',
            'wg_customer_investigation_al_measure_tracking.description',
            'wg_customer_investigation_al_measure_tracking.folio',
            'wg_customer_investigation_al_measure_tracking.isActive'
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
            $this->customerDocumentRepository->sortBy('wg_customer_investigation_al_measure_tracking.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_measure_tracking.customer_investigation_measure_id', $customerInvestigationMeasureId);

        if (strlen(trim($search)) > 0) {
            //$filters[] = array('investigation_measure.item', $search);
            $filters[] = array('investigation_control_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_measure.description', $search);
            $filters[] = array('wg_customer_investigation_al_measure.checkDate', $search);
            $filters[] = array('wg_customer_investigation_al_measure.responsible', $search);
            $filters[] = array('investigation_measure_tracking_status.item', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_measure_tracking.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationMeasureId)
    {

        $model = new CustomerInvestigationAlMeasureTracking();
        $this->customerDocumentRepository = new CustomerInvestigationAlMeasureTrackingRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_measure_tracking.customer_investigation_measure_id', $customerInvestigationMeasureId);

        if (strlen(trim($search)) > 0) {
            //$filters[] = array('investigation_measure.item', $search);
            $filters[] = array('investigation_control_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_measure.description', $search);
            $filters[] = array('wg_customer_investigation_al_measure.checkDate', $search);
            $filters[] = array('wg_customer_investigation_al_measure.responsible', $search);
            $filters[] = array('investigation_measure_tracking_status.item', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_measure_tracking.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByInvestigation($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationId = 0) {

        $model = new CustomerInvestigationAlMeasureTracking();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerInvestigationAlMeasureTrackingRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_measure_tracking.id',
            'wg_customer_investigation_al_measure_tracking.type',
            'wg_customer_investigation_al_measure_tracking.description',
            'wg_customer_investigation_al_measure_tracking.folio',
            'wg_customer_investigation_al_measure_tracking.isActive'
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
            $this->customerDocumentRepository->sortBy('wg_customer_investigation_al_measure_tracking.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_measure.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            //$filters[] = array('investigation_measure.item', $search);
            $filters[] = array('investigation_control_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_measure.description', $search);
            $filters[] = array('wg_customer_investigation_al_measure.checkDate', $search);
            $filters[] = array('wg_customer_investigation_al_measure.responsible', $search);
            $filters[] = array('investigation_measure_tracking_status.item', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_measure_tracking.*']);

        return $this->customerDocumentRepository->getFilteredOptionalInvestigation($filters, false, "");
    }

    public function getCountInvestigation($search = "", $customerInvestigationId)
    {

        $model = new CustomerInvestigationAlMeasureTracking();
        $this->customerDocumentRepository = new CustomerInvestigationAlMeasureTrackingRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_measure.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            //$filters[] = array('investigation_measure.item', $search);
            $filters[] = array('investigation_control_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_measure.description', $search);
            $filters[] = array('wg_customer_investigation_al_measure.checkDate', $search);
            $filters[] = array('wg_customer_investigation_al_measure.responsible', $search);
            $filters[] = array('investigation_measure_tracking_status.item', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_measure_tracking.*']);

        return $this->customerDocumentRepository->getFilteredOptionalInvestigation($filters, true, "");
    }
}
