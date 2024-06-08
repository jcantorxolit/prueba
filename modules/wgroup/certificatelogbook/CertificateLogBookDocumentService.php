<?php

namespace Wgroup\CertificateLogBookDocument;

use DB;
use Exception;
use Log;
use Str;

class CertificateLogBookDocumentService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $certificateGradeParticipantId = 0) {

        $model = new CertificateLogBookDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CertificateLogBookDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_certificate_grade_participant_logbook.id',
            'wg_certificate_grade_participant_logbook.eventDate',
            'wg_certificate_grade_participant_logbook.company',
            'wg_certificate_grade_participant_logbook.description',
            'wg_certificate_grade_participant_logbook.location',
            'wg_certificate_grade_participant_logbook.hourWorked',
            'wg_certificate_grade_participant_logbook.maxHeightWorked',
            'wg_certificate_grade_participant_logbook.version',
            'wg_certificate_grade_participant_logbook.status'
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
            $this->customerDocumentRepository->sortBy('wg_certificate_grade_participant_logbook.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_certificate_grade_participant_logbook.certificate_grade_participant_id', $certificateGradeParticipantId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_certificate_grade_participant_logbook.company', $search);
            $filters[] = array('wg_certificate_grade_participant_logbook.description', $search);
            $filters[] = array('wg_certificate_grade_participant_logbook.location', $search);
            $filters[] = array('wg_certificate_grade_participant_logbook.eventDate', $search);
            $filters[] = array('wg_certificate_grade_participant_logbook.version', $search);
            $filters[] = array('wg_certificate_grade_participant_logbook.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_certificate_grade_participant_logbook.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_certificate_grade_participant_logbook.status', '0');
        }


        $this->customerDocumentRepository->setColumns(['wg_certificate_grade_participant_logbook.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $certificateGradeParticipant = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select DISTINCT d.id, d.certificate_grade_participant_id, d.description, d.eventDate, d.company, d.location, d.hourWorked, d.maxHeightWorked, d.version, '' agent
            , d.created_at
from wg_certificate_grade_participant_logbook d
where d.category = 'experience' and (d.certificate_grade_participant_id = :certificate_grade_participant_id)";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (d.company like '%$search%' or d.description like '%$search%' or d.location like '%$search%')";
            $query.=$where;
        }

        $order = " Order by d.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'certificate_grade_participant_id' => $certificateGradeParticipant
        ));

        return $results;

    }

    public function getCount($search = "", $certificateGradeParticipant) {

        $query = "select DISTINCT d.id, d.certificate_grade_participant_id, d.description, d.eventDate, d.company, d.location, d.hourWorked, d.maxHeightWorked, d.version, '' agent
            , d.created_at
from wg_certificate_grade_participant_logbook d
where d.category = 'experience' and (d.certificate_grade_participant_id = :certificate_grade_participant_id)";


        if ($search != "") {
            $where = " AND (d.company like '%$search%' or d.description like '%$search%' or d.location like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'certificate_grade_participant_id' => $certificateGradeParticipant
        ));

        return $results;
    }
}
