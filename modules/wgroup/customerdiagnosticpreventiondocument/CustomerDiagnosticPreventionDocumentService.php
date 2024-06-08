<?php

namespace Wgroup\CustomerDiagnosticPreventionDocument;

use DB;
use Exception;
use Log;
use Str;

class CustomerDiagnosticPreventionDocumentService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerEmployeeDocumentRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0)
    {

        $model = new CustomerDiagnosticPreventionDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerEmployeeDocumentRepository = new CustomerDiagnosticPreventionDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerEmployeeDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_diagnostic_prevention_document.id',
            'wg_customer_diagnostic_prevention_document.classification',
            'wg_customer_diagnostic_prevention_document.name',
            'wg_customer_diagnostic_prevention_document.code',
            'wg_customer_diagnostic_prevention_document.description',
            'wg_customer_diagnostic_prevention_document.version',
            'wg_customer_diagnostic_prevention_document.status'
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
                    $this->customerEmployeeDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerEmployeeDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerEmployeeDocumentRepository->sortBy('wg_customer_diagnostic_prevention_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_diagnostic_prevention_document.customer_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic_prevention_document.classification', $search);
            $filters[] = array('wg_customer_diagnostic_prevention_document.code', $search);
            $filters[] = array('wg_customer_diagnostic_prevention_document.name', $search);
            $filters[] = array('wg_customer_diagnostic_prevention_document.description', $search);
            $filters[] = array('wg_customer_diagnostic_prevention_document.version', $search);
            $filters[] = array('wg_customer_diagnostic_prevention_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_diagnostic_prevention_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_diagnostic_prevention_document.status', '0');
        }


        $this->customerEmployeeDocumentRepository->setColumns(['wg_customer_diagnostic_prevention_document.*']);

        return $this->customerEmployeeDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0)
    {
        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
	SELECT d.id,
					param.`item` classification,
					d.`customer_id`,
					d.`code`,
					d.`name`,
					d.description,
					d.version,
					 d.startDate ,
					 d.endDate ,
					 d.created_at ,
					 wg_status.item status
	FROM wg_customer_diagnostic_prevention_document d
	LEFT JOIN
		(SELECT `value`,
						namespace,
						`group`,
						item
		 FROM system_parameters
		 WHERE namespace = 'wgroup'
			 AND `group` = 'program_prevention_document_classification') param ON d.classification = param.`value`
	LEFT JOIN
		(SELECT *
		 FROM system_parameters sp
		 WHERE sp.`group` = 'customer_document_status') wg_status ON d.status COLLATE utf8_general_ci = wg_status.value
     WHERE d.customer_id = :customer_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";

        }


        $query .= $where;

        $order = " Order by p.created_at DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;

    }

    public function getCount($search = "", $customerId = 0)
    {

        $query = "SELECT * FROM (
	SELECT d.id,
					param.`item` classification,
					d.`customer_id`,
					d.`code`,
					d.`name`,
					d.description,
					d.version,
					 d.startDate ,
					 d.endDate ,
					 d.created_at ,
					 wg_status.item status
	FROM wg_customer_diagnostic_prevention_document d
	LEFT JOIN
		(SELECT `value`,
						namespace,
						`group`,
						item
		 FROM system_parameters
		 WHERE namespace = 'wgroup'
			 AND `group` = 'program_prevention_document_classification') param ON d.classification = param.`value`
	LEFT JOIN
		(SELECT *
		 FROM system_parameters sp
		 WHERE sp.`group` = 'customer_document_status') wg_status ON d.status COLLATE utf8_general_ci = wg_status.value
     WHERE d.customer_id = :customer_id
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;
    }

    public function getAllBySearchQuestion($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $questionId = 0)
    {
        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
	SELECT d.id,
					param.`item` classification,
					d.`customer_id`,
					d.`code`,
					d.`name`,
					d.description,
					d.version,
					 d.startDate ,
					 d.endDate ,
					 d.created_at ,
					 wg_status.item status
	FROM wg_customer_diagnostic_prevention_document d
	LEFT JOIN
		(SELECT `value`,
						namespace,
						`group`,
						item
		 FROM system_parameters
		 WHERE namespace = 'wgroup'
			 AND `group` = 'program_prevention_document_classification') param ON d.classification = param.`value`
	LEFT JOIN
		(SELECT *
		 FROM system_parameters sp
		 WHERE sp.`group` = 'customer_document_status') wg_status ON d.status COLLATE utf8_general_ci = wg_status.value
     WHERE d.customer_id = :customer_id and d.id IN (			SELECT
				customer_diagnostic_prevention_document_id
			FROM
				wg_customer_diagnostic_prevention_document_question
			WHERE
				program_prevention_question_id = $questionId)
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";

        }


        $query .= $where;

        $order = " Order by p.created_at DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;

    }

    public function getQuestionCount($search = "", $customerId = 0, $questionId = 0)
    {

        $query = "SELECT * FROM (
	SELECT d.id,
					param.`item` classification,
					d.`customer_id`,
					d.`code`,
					d.`name`,
					d.description,
					d.version,
					 d.startDate ,
					 d.endDate ,
					 d.created_at ,
					 wg_status.item status
	FROM wg_customer_diagnostic_prevention_document d
	LEFT JOIN
		(SELECT `value`,
						namespace,
						`group`,
						item
		 FROM system_parameters
		 WHERE namespace = 'wgroup'
			 AND `group` = 'program_prevention_document_classification') param ON d.classification = param.`value`
	LEFT JOIN
		(SELECT *
		 FROM system_parameters sp
		 WHERE sp.`group` = 'customer_document_status') wg_status ON d.status COLLATE utf8_general_ci = wg_status.value
     WHERE d.customer_id = :customer_id and d.id IN (			SELECT
				customer_diagnostic_prevention_document_id
			FROM
				wg_customer_diagnostic_prevention_document_question
			WHERE
				program_prevention_question_id = $questionId)
) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.classification like '%$search%' or p.description like '%$search%' or p.name like '%$search%')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE " . $where;
    }
}
