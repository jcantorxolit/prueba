<?php

namespace Wgroup\MinimumStandardItemQuestion;

use DB;
use Exception;
use Log;
use Str;

class MinimumStandardItemQuestionService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

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

        $model = new MinimumStandardItemQuestion();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new MinimumStandardItemQuestionRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
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
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_minimum_standard_item_detail.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_minimum_standard_item_detail.minimum_standard_item_id', $minimumStandardItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_minimum_standard_item_detail.type', $search);
            $filters[] = array('wg_minimum_standard_item_detail.description', $search);
            $filters[] = array('wg_minimum_standard_item.numeral', $search);
            $filters[] = array('wg_minimum_standard_item.description', $search);
        }

        $this->repository->setColumns(['wg_minimum_standard_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $minimumStandardItemId)
    {
        $model = new MinimumStandardItemQuestion();
        $this->repository = new MinimumStandardItemQuestionRepository($model);

        $filters = array();

        $filters[] = array('wg_minimum_standard_item_detail.minimum_standard_item_id', $minimumStandardItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_minimum_standard_item_detail.type', $search);
            $filters[] = array('wg_minimum_standard_item_detail.description', $search);
            $filters[] = array('wg_minimum_standard_item.numeral', $search);
            $filters[] = array('wg_minimum_standard_item.description', $search);
        }

        $this->repository->setColumns(['wg_minimum_standard_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }


    public function getAllAvailableQuestions($search, $minimumStandardItemId = 0)
    {
        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.minimum_standard_item_id minimumStandardItemId,
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
        select * from wg_minimum_standard_item_question where minimum_standard_item_id = :minimum_standard_item_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo' and ppd.id is null
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.program like '%$search%' or p.category like '%$search%' or p.question like '%$search%' or p.article like '%$search%')";
        }

        $query .= $where;

        $order = "";

        $query .= $order;

        $results = DB::select($query, array(
            "minimum_standard_item_id" => $minimumStandardItemId
        ));

        return $results;

    }

    public function getAllAvailableQuestionsCount($search = "", $minimumStandardItemId = 0)
    {

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.minimum_standard_item_id minimumStandardItemId,
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
        select * from wg_minimum_standard_item_question where minimum_standard_item_id = :minimum_standard_item_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo' and ppd.id is null
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.program like '%$search%' or p.category like '%$search%' or p.question like '%$search%' or p.article like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "minimum_standard_item_id" => $minimumStandardItemId
        ));

        return count($results);
    }

    public function getAllSelectedQuestions($search, $minimumStandardItemId = 0)
    {
        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.minimum_standard_item_id minimumStandardItemId,
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
        select * from wg_minimum_standard_item_question where minimum_standard_item_id = :minimum_standard_item_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.program like '%$search%' or p.category like '%$search%' or p.question like '%$search%' or p.article like '%$search%')";
        }

        $query .= $where;

        $order = "";

        $query .= $order;

        $results = DB::select($query, array(
            "minimum_standard_item_id" => $minimumStandardItemId
        ));

        return $results;

    }

    public function getAllSelectedQuestionsCount($search = "", $minimumStandardItemId = 0)
    {

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.minimum_standard_item_id minimumStandardItemId,
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
        select * from wg_minimum_standard_item_question where minimum_standard_item_id = :minimum_standard_item_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.program like '%$search%' or p.category like '%$search%' or p.question like '%$search%' or p.article like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "minimum_standard_item_id" => $minimumStandardItemId
        ));

        return count($results);
    }
}
