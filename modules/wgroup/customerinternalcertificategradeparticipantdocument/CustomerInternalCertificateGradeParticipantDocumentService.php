<?php

namespace Wgroup\CustomerInternalCertificateGradeParticipantDocument;

use DB;
use Exception;
use Log;
use Str;

class CustomerInternalCertificateGradeParticipantDocumentService {

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

        $model = new CustomerInternalCertificateGradeParticipantDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerInternalCertificateGradeParticipantDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_certificate_grade_participant_document.id',
            'wg_customer_internal_certificate_grade_participant_document.requirement',
            'wg_customer_internal_certificate_grade_participant_document.description',
            'wg_customer_internal_certificate_grade_participant_document.version',
            'wg_customer_internal_certificate_grade_participant_document.status'
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
            $this->customerDocumentRepository->sortBy('wg_customer_internal_certificate_grade_participant_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_internal_certificate_grade_participant_document.certificate_grade_participant_id', $certificateGradeParticipantId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_certificate_grade_participant_document.requirement', $search);
            $filters[] = array('wg_customer_internal_certificate_grade_participant_document.description', $search);
            $filters[] = array('wg_customer_internal_certificate_grade_participant_document.version', $search);
            $filters[] = array('wg_customer_internal_certificate_grade_participant_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_certificate_grade_participant_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_certificate_grade_participant_document.status', '0');
        }


        $this->customerDocumentRepository->setColumns(['wg_customer_internal_certificate_grade_participant_document.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $certificateGradeParticipant = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select DISTINCT d.id, d.customer_internal_certificate_grade_participant_id, p.item requeriment, d.description, d.version, users.name agent, d.created_at
from wg_customer_internal_certificate_grade_participant_document d
inner join `users` on `d`.`createdBy` = `users`.`id`
left join (select * from system_parameters where `group` = 'certificate_program_requirement') p on d.requirement COLLATE utf8_general_ci = p.value
where (d.customer_internal_certificate_grade_participant_id = :certificate_grade_participant_id)";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.item like '%$search%' or d.description like '%$search%')";
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

        $query = "select DISTINCT d.id, d.customer_internal_certificate_grade_participant_id, p.item documentType, d.description, d.version, '' agent
            , d.created_at
from wg_customer_internal_certificate_grade_participant_document d
left join (select * from system_parameters where `group` = 'certificate_program_requirement') p on d.requirement COLLATE utf8_general_ci = p.value
where (d.customer_internal_certificate_grade_participant_id = :certificate_grade_participant_id)";

        if ($search != "") {
            $where = " AND (p.item like '%$search%' or d.description like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'certificate_grade_participant_id' => $certificateGradeParticipant
        ));

        return $results;
    }
}
