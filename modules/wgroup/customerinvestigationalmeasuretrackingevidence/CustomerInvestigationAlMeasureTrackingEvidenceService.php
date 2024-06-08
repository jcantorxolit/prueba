<?php

namespace Wgroup\CustomerInvestigationAlMeasureTrackingEvidence;

use DB;
use Exception;
use Log;
use Str;


class CustomerInvestigationAlMeasureTrackingEvidenceService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationMeasureTrackingId = 0) {

        $model = new CustomerInvestigationAlMeasureTrackingEvidence();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerInvestigationAlMeasureTrackingEvidenceRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_measure_tracking_evidence.id',
            'wg_customer_investigation_al_measure_tracking_evidence.type',
            'wg_customer_investigation_al_measure_tracking_evidence.description',
            'wg_customer_investigation_al_measure_tracking_evidence.folio',
            'wg_customer_investigation_al_measure_tracking_evidence.isActive'
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
            $this->customerDocumentRepository->sortBy('wg_customer_investigation_al_measure_tracking_evidence.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_measure_tracking_evidence.customer_investigation_measure_tracking_id', $customerInvestigationMeasureTrackingId);

        if (strlen(trim($search)) > 0) {
            //$filters[] = array('investigation_measure.item', $search);
            $filters[] = array('investigation_measure_tracking_evidence_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_measure_tracking_evidence.description', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_measure_tracking_evidence.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationMeasureTrackingId)
    {

        $model = new CustomerInvestigationAlMeasureTrackingEvidence();
        $this->customerDocumentRepository = new CustomerInvestigationAlMeasureTrackingEvidenceRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_measure_tracking_evidence.customer_investigation_measure_tracking_id', $customerInvestigationMeasureTrackingId);

        if (strlen(trim($search)) > 0) {
            //$filters[] = array('investigation_measure.item', $search);
            $filters[] = array('investigation_measure_tracking_evidence_type.item', $search);
            $filters[] = array('wg_customer_investigation_al_measure_tracking_evidence.description', $search);
        }

        $this->customerDocumentRepository->setColumns(['wg_customer_investigation_al_measure_tracking_evidence.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, true, "");
    }

}
