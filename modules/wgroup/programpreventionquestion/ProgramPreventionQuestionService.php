<?php

namespace Wgroup\ProgramPreventionQuestion;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class ProgramPreventionQuestionService
{

    protected static $instance;
    protected $sessionKey = 'service_agent';
    protected $employeeRepository;

    function __construct()
    {
        //$this->employeeRepository = new CustomerReporistory();
    }

    public function init()
    {
        parent::init();
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new ProgramPreventionQuestion();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new ProgramPreventionQuestionRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_progam_prevention_question.description',
            'wg_progam_prevention_question.status',
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
                    $this->employeeRepository->sortBy($colName, $dir);
                } else {
                    $this->employeeRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->employeeRepository->sortBy('wg_progam_prevention_question.code', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_progam_prevention_question.description', $search);
            $filters[] = array('wg_progam_prevention_question.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_progam_prevention_question.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_progam_prevention_question.isActive', '0');
        }

        $this->employeeRepository->setColumns(['wg_progam_prevention_question.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new ProgramPreventionQuestion();
        $this->employeeRepository = new ProgramPreventionQuestionRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_progam_prevention_question.description', $search);
            $filters[] = array('wg_progam_prevention_question.status', $search);
        }

        $this->employeeRepository->setColumns(['wg_progam_prevention_question.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
        select
            ppq.id,
            pp.`name` program,
            ppc.`name` category,
            ppq.description question,
            ppq.article,
            ppq.guide,
            GROUP_CONCAT(DISTINCT wg_customer_size.item
                      ORDER BY wg_customer_size.id DESC SEPARATOR ',') classification
    from
        wg_progam_prevention pp
    inner join wg_progam_prevention_category ppc on pp.id = ppc.program_id
    inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
    left join wg_progam_prevention_question_classification ppqc on ppq.id = ppqc.program_prevention_question_id
    left join (SELECT `id`, `namespace`, `group`, `item`, `value` COLLATE utf8_general_ci AS `value`
                FROM `system_parameters` WHERE `namespace` = 'wgroup' AND `group` = 'wg_customer_size') wg_customer_size ON wg_customer_size.value = ppqc.customer_size
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
    GROUP BY ppq.id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.question like '%$search%' or p.program like '%$search%' or p.category like '%$search%' or p.article like '%$search%' or p.guide like '%$search%' or p.classification like '%$search%')";
        }

        $query .= $where;

        $order = "";

        $query .= $order;

        $results = DB::select($query);

        return $results;

    }

    public function getAllBySearchCount($search = "")
    {

        $query = "SELECT * FROM (
        select
            ppq.id,
            pp.`name` program,
            ppc.`name` category,
            ppq.description question,
            ppq.article,
            ppq.guide
    from
        wg_progam_prevention pp
    inner join wg_progam_prevention_category ppc on pp.id = ppc.program_id
    inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
    where pp.status = 'activo' and ppc.status = 'activo' and ppq.status = 'activo'
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.question like '%$search%' or p.program like '%$search%' or p.category like '%$search%' or p.article like '%$search%' or p.guide like '%$search%')";
        }


        $query .= $where;

        $results = DB::select($query);

        return $results;
    }
}
