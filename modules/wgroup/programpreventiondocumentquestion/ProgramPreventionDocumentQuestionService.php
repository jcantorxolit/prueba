<?php

namespace Wgroup\ProgramPreventionDocumentQuestion;

use DB;
use Exception;
use Log;
use Str;

class ProgramPreventionDocumentQuestionService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new ProgramPreventionDocumentQuestion();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new ProgramPreventionDocumentQuestionRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_progam_prevention_document_question.id',
            'wg_progam_prevention_document_question.program_prevention_document_id',
            'wg_progam_prevention_document_question.program_prevention_question_id',
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
            $this->customerContractorRepository->sortBy('wg_progam_prevention_document_question.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_progam_prevention_document_question.program_prevention_document_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_progam_prevention_document_question.id', $search);
            $filters[] = array('wg_progam_prevention_document_question.program_prevention_document_id', $search);
            $filters[] = array('wg_progam_prevention_document_question.program_prevention_question_id', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_progam_prevention_document_question.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new ProgramPreventionDocumentQuestion();
        $this->customerContractorRepository = new ProgramPreventionDocumentQuestionRepository($model);

        $filters = array();

        $filters[] = array('wg_progam_prevention_document_question.program_prevention_document_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_progam_prevention_document_question.id', $search);
            $filters[] = array('wg_progam_prevention_document_question.program_prevention_document_id', $search);
            $filters[] = array('wg_progam_prevention_document_question.program_prevention_question_id', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_progam_prevention_document_question.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $programPreventionDocumentId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.program_prevention_document_id programPreventionDocumentId,
            ppq.id programPreventionQuestionId,
            pp.`name` program,
            ppc.`name` category,
            ppq.description question,
            ppq.article,
            ppq.guide,
            case when ppd.program_prevention_question_id is null then 0 else 1 end selected
    from
        wg_progam_prevention pp
    inner join wg_progam_prevention_category ppc on pp.id = ppc.program_id
    inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
    left join (
        select * from wg_progam_prevention_document_question where program_prevention_document_id = :program_prevention_document_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo' and ppd.id is null
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";

        }


        $query .= $where;

        $order = "";

        $query .= $order;

        $results = DB::select($query, array(
            "program_prevention_document_id" => $programPreventionDocumentId
        ));

        return $results;

    }

    public function getAllBySearchCount($search = "", $programPreventionDocumentId = 0)
    {

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.program_prevention_document_id programPreventionDocumentId,
            ppq.id programPreventionQuestionId,
            pp.`name` program,
            ppc.`name` category,
            ppq.article,
            ppq.description question,
            ppq.guide,
            case when ppd.program_prevention_question_id is null then 0 else 1 end selected
    from
        wg_progam_prevention pp
    inner join wg_progam_prevention_category ppc on pp.id = ppc.program_id
    inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
    left join (
        select * from wg_progam_prevention_document_question where program_prevention_document_id = :program_prevention_document_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo' and ppd.id is null
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "program_prevention_document_id" => $programPreventionDocumentId
        ));

        return $results;
    }

    public function getAllBySearchSelected($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $programPreventionDocumentId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.program_prevention_document_id programPreventionDocumentId,
            ppq.id programPreventionQuestionId,
            pp.`name` program,
            ppc.`name` category,
            ppq.description question,
            ppq.article,
            ppq.guide,
            case when ppd.program_prevention_question_id is null then 0 else 1 end selected
    from
        wg_progam_prevention pp
    inner join wg_progam_prevention_category ppc on pp.id = ppc.program_id
    inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
    inner join (
        select * from wg_progam_prevention_document_question where program_prevention_document_id = :program_prevention_document_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";

        }


        $query .= $where;

        $order = "";

        $query .= $order;

        $results = DB::select($query, array(
            "program_prevention_document_id" => $programPreventionDocumentId
        ));

        return $results;

    }

    public function getAllBySearchSelectedCount($search = "", $programPreventionDocumentId = 0)
    {

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.program_prevention_document_id programPreventionDocumentId,
            ppq.id programPreventionQuestionId,
            pp.`name` program,
            ppc.`name` category,
            ppq.description question,
            ppq.article,
            ppq.guide,
            case when ppd.program_prevention_question_id is null then 0 else 1 end selected
    from
        wg_progam_prevention pp
    inner join wg_progam_prevention_category ppc on pp.id = ppc.program_id
    inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
    inner join (
        select * from wg_progam_prevention_document_question where program_prevention_document_id = :program_prevention_document_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "program_prevention_document_id" => $programPreventionDocumentId
        ));

        return $results;
    }
}
