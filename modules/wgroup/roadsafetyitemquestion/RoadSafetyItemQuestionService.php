<?php

namespace Wgroup\RoadSafetyItemQuestion;

use DB;
use Exception;
use Log;
use Str;

class RoadSafetyItemQuestionService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $roadSafetyItemId = 0)
    {

        $model = new RoadSafetyItemQuestion();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new RoadSafetyItemQuestionRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_road_safety_item_detail.id',
            'wg_road_safety_item_detail.road_safety_item_id',
            'wg_road_safety_item_detail.type',
            'wg_road_safety_item_detail.description'
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
            $this->repository->sortBy('wg_road_safety_item_detail.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_road_safety_item_detail.road_safety_item_id', $roadSafetyItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_road_safety_item_detail.type', $search);
            $filters[] = array('wg_road_safety_item_detail.description', $search);
            $filters[] = array('wg_road_safety_item.numeral', $search);
            $filters[] = array('wg_road_safety_item.description', $search);
        }

        $this->repository->setColumns(['wg_road_safety_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $roadSafetyItemId)
    {
        $model = new RoadSafetyItemQuestion();
        $this->repository = new RoadSafetyItemQuestionRepository($model);

        $filters = array();

        $filters[] = array('wg_road_safety_item_detail.road_safety_item_id', $roadSafetyItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_road_safety_item_detail.type', $search);
            $filters[] = array('wg_road_safety_item_detail.description', $search);
            $filters[] = array('wg_road_safety_item.numeral', $search);
            $filters[] = array('wg_road_safety_item.description', $search);
        }

        $this->repository->setColumns(['wg_road_safety_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }


    public function getAllAvailableQuestions($search, $roadSafetyItemId = 0)
    {
        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.road_safety_item_id roadSafetyItemId,
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
        select * from wg_road_safety_item_question where road_safety_item_id = :road_safety_item_id
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
            "road_safety_item_id" => $roadSafetyItemId
        ));

        return $results;

    }

    public function getAllAvailableQuestionsCount($search = "", $roadSafetyItemId = 0)
    {

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.road_safety_item_id roadSafetyItemId,
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
        select * from wg_road_safety_item_question where road_safety_item_id = :road_safety_item_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo' and ppd.id is null
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.program like '%$search%' or p.category like '%$search%' or p.question like '%$search%' or p.article like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "road_safety_item_id" => $roadSafetyItemId
        ));

        return count($results);
    }

    public function getAllSelectedQuestions($search, $roadSafetyItemId = 0)
    {
        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.road_safety_item_id roadSafetyItemId,
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
        select * from wg_road_safety_item_question where road_safety_item_id = :road_safety_item_id
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
            "road_safety_item_id" => $roadSafetyItemId
        ));

        return $results;

    }

    public function getAllSelectedQuestionsCount($search = "", $roadSafetyItemId = 0)
    {

        $query = "SELECT * FROM (
        select
            ppd.id,
            ppd.road_safety_item_id roadSafetyItemId,
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
        select * from wg_road_safety_item_question where road_safety_item_id = :road_safety_item_id
    ) ppd on ppq.id = ppd.program_prevention_question_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.program like '%$search%' or p.category like '%$search%' or p.question like '%$search%' or p.article like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "road_safety_item_id" => $roadSafetyItemId
        ));

        return count($results);
    }
}
