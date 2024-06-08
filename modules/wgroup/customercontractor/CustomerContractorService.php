<?php

namespace Wgroup\CustomerContractor;

use DB;
use Exception;
use Log;
use Str;

class CustomerContractorService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0)
    {

        $model = new CustomerContractor();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerContractorRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_contractor.id',
            'wg_customer_contractor.customer_id',
            'wg_customer_contractor.contractor_id',
            'wg_customer_contractor.contractor_type_id',
            'wg_customer_contractor.contract',
            'wg_customer_contractor.isActive',

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
            $this->customerContractorRepository->sortBy('wg_customer_contractor.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_contractor.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_contractor.id', $search);
            $filters[] = array('wg_customer_contractor.contractor_id', $search);
            $filters[] = array('wg_customer_contractor.contract', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_contractor.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_contractor.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_contractor.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerContractor();
        $this->customerContractorRepository = new CustomerContractorRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_contractor.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_contractor.id', $search);
            $filters[] = array('wg_customer_contractor.contract', $search);
            $filters[] = array('wg_customer_contractor.contractor_id', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_contractor.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllContactBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $contractor = 0)
    {

        $model = new CustomerContractor();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerContractorRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_contractor.id'
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
            $this->customerContractorRepository->sortBy('wg_customer_contractor.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_contractor.contractor_id', $customerId);

        if ($contractor != 0) {
            $filters[] = array('wg_customer_contractor.customer_id', $contractor);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_contractor.id', $search);
            $filters[] = array('wg_customer_contractor.contractor_id', $search);
            $filters[] = array('wg_customer_contractor.contract', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_contractor.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_contractor.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_contractor.*']);

        $filterContractor = $contractor != 0;

        return $this->customerContractorRepository->getFilterContract($filters, false, "", $filterContractor);
    }

    public function getContractCount($search = "", $customerId, $contractor = 0)
    {

        $model = new CustomerContractor();
        $this->customerContractorRepository = new CustomerContractorRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_contractor.contractor_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_contractor.id', $search);
            $filters[] = array('wg_customer_contractor.contract', $search);
            $filters[] = array('wg_customer_contractor.contractor_id', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_contractor.*']);

        return $this->customerContractorRepository->getFilterContract($filters, true, "");
    }

    public function getAllSummaryBy($sorting = array(), $contractorId)
    {

        $columnNames = ["period", "requirements", "answers", "advance",  "average", "startDate", "endDate", "status"];
        $columnOrder = "period";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "period") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "SELECT
		period, requirements , answers
		, ROUND(IFNULL((answers / requirements) * 100, 0), 2) advance
		, ROUND(IFNULL((total / requirements), 0), 2) average
		, IFNULL(total, 0) total
		, name fullName
		, startDate
		, endDate
		, CASE WHEN answers = requirements THEN 'Completado' WHEN answers > 0 THEN 'Iniciado' ELSE 'Sin Iniciar' END `status`
FROM (
		SELECT
				ccd.period, count(*) requirements
				, SUM(CASE WHEN ISNULL(ccd.rate_id) then 0 else 1 end) answers
				, SUM(wg_rate.value) total
				, MIN(ccd.updated_at) startDate
				, MAX(ccd.updated_at) endDate
				, u.name
		FROM
				wg_customer_contract_detail ccd
		INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
		INNER JOIN
		(
						SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
						UNION ALL
						SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
						UNION ALL
						SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
						UNION ALL
						SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
						UNION ALL
						SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
						UNION ALL
						SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
						UNION ALL
						SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
						UNION ALL
						SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
						UNION ALL
						SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
						UNION ALL
						SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
						UNION ALL
						SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
						UNION ALL
						SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
			) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
		LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
		INNER JOIN users u ON ccd.createdBy = u.id
		WHERE ccd.contractor_id = :contractor_id
		  AND cpr.isActive = 1
		GROUP BY ccd.period ) periodic
  ORDER BY $columnOrder $dirOrder";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId
        ));

        return $results;
    }

    public function getSummaryByPeriod($contractorId, $period, $sorting)
    {
		$columnNames = ["requirement"];
        $columnOrder = "requirement";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "period") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
		}

        $query = "SELECT requirement ,
       CASE
           WHEN r.text IS NULL THEN 'Sin evaluar'
           ELSE r.text
       END rate ,
       CASE
           WHEN ccdd.quantity IS NULL THEN 0
           ELSE 1
       END hasAttachment
FROM wg_customer_contract_detail ccd
INNER JOIN
(
				SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
				UNION ALL
				SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
				UNION ALL
				SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
				UNION ALL
				SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
				UNION ALL
				SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
				UNION ALL
				SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
				UNION ALL
				SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
				UNION ALL
				SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
				UNION ALL
				SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
				UNION ALL
				SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
				UNION ALL
				SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
				UNION ALL
				SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
	) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
LEFT JOIN wg_rate r ON ccd.rate_id = r.id
LEFT JOIN wg_customer_contract_detail_action_plan ccdap ON ccd.id = ccdap.contract_detail_id
LEFT JOIN
  (SELECT count(*) quantity,
  customer_contract_detail_id
   FROM wg_customer_contract_detail_document
   GROUP BY customer_contract_detail_id) ccdd ON ccd.id = ccdd.customer_contract_detail_id
LEFT JOIN
  (SELECT ccdapr.*,
          c.`name`,
          c.firstName,
          c.lastName
   FROM wg_customer_contract_detail_action_plan_resp ccdapr
   INNER JOIN wg_contact c ON ccdapr.contact_id = c.id LIMIT 1) rs ON ccdap.id = rs.contract_action_plan_id
WHERE contractor_id = :contractor_id
  AND period = $period
  ORDER BY $columnOrder $dirOrder";

        ////Log::info($query);

        $results = DB::select($query, array(
            'contractor_id' => $contractorId
        ));

        return $results;

    }

    public function getSummaryByPeriodExport($contractorId, $period)
    {
        $query = "SELECT requirement Requisito ,
       CASE
           WHEN ccdd.quantity IS NULL THEN 'No'
           ELSE 'Si'
       END Anexos ,
       CASE
           WHEN r.text IS NULL THEN 'Sin evaluar'
           ELSE r.text
       END Estado
FROM wg_customer_contract_detail ccd
INNER JOIN
(
				SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
				UNION ALL
				SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
				UNION ALL
				SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
				UNION ALL
				SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
				UNION ALL
				SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
				UNION ALL
				SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
				UNION ALL
				SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
				UNION ALL
				SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
				UNION ALL
				SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
				UNION ALL
				SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
				UNION ALL
				SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
				UNION ALL
				SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
	) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
LEFT JOIN wg_rate r ON ccd.rate_id = r.id
LEFT JOIN wg_customer_contract_detail_action_plan ccdap ON ccd.id = ccdap.contract_detail_id
LEFT JOIN
  (SELECT count(*) quantity,
  customer_contract_detail_id
   FROM wg_customer_contract_detail_document
   GROUP BY customer_contract_detail_id) ccdd ON ccd.id = ccdd.customer_contract_detail_id
LEFT JOIN
  (SELECT ccdapr.*,
          c.`name`,
          c.firstName,
          c.lastName
   FROM wg_customer_contract_detail_action_plan_resp ccdapr
   INNER JOIN wg_contact c ON ccdapr.contact_id = c.id LIMIT 1) rs ON ccdap.id = rs.contract_action_plan_id
WHERE contractor_id = :contractor_id
  AND period = $period";

        ////Log::info($query);

        $results = DB::select($query, array(
            'contractor_id' => $contractorId
        ));

        return $results;

    }

    public function getDashboardPie($contractorId)
    {
        $sql = "SELECT
		period label
		, ROUND(IFNULL((total / requirements),0), 2) value
		, '#F44336' color
    , '#FFCDD2' highlightColor
FROM (
		SELECT
				ccd.period, count(*) requirements
				, SUM(CASE WHEN ISNULL(ccd.rate_id) then 0 else 1 end) answers
				, SUM(wg_rate.`value`) total
		FROM
				wg_customer_contract_detail ccd
		INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
		INNER JOIN
			(
							SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
							UNION ALL
							SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
							UNION ALL
							SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
							UNION ALL
							SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
							UNION ALL
							SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
							UNION ALL
							SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
							UNION ALL
							SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
							UNION ALL
							SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
							UNION ALL
							SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
							UNION ALL
							SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
							UNION ALL
							SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
							UNION ALL
							SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
				) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
		LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
		INNER JOIN users u ON ccd.createdBy = u.id
		WHERE ccd.contractor_id = :contractor_id and cpr.isActive = 1
		GROUP BY ccd.period
		) periodic";

        $results = DB::select($sql, array(
            'contractor_id' => $contractorId
        ));

        return $results;
    }

    public function getDashboardBar($contractorId)
    {
        $sql = "SELECT
		period label
		, ROUND(IFNULL((total / requirements),0), 2) value
		, '#F44336' color
    , '#FFCDD2' highlightColor
FROM (
		SELECT
				ccd.period, count(*) requirements
				, SUM(CASE WHEN ISNULL(ccd.rate_id) then 0 else 1 end) answers
				, SUM(wg_rate.`value`) total
		FROM
				wg_customer_contract_detail ccd
		INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
		INNER JOIN
			(
							SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
							UNION ALL
							SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
							UNION ALL
							SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
							UNION ALL
							SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
							UNION ALL
							SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
							UNION ALL
							SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
							UNION ALL
							SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
							UNION ALL
							SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
							UNION ALL
							SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
							UNION ALL
							SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
							UNION ALL
							SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
							UNION ALL
							SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
				) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
		LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
		INNER JOIN users u ON ccd.createdBy = u.id
		WHERE ccd.contractor_id = :contractor_id and cpr.isActive = 1
		GROUP BY ccd.period
		) periodic";

        $results = DB::select($sql, array(
            'contractor_id' => $contractorId
        ));

        return [];
        //return $results;
    }

    public function getDashboardTotal($contractorId)
    {
        $sql = "SELECT
				ROUND((SUM(IFNULL(wg_rate.value ,0)) / count(*)), 2) total
		FROM
				wg_customer_contract_detail ccd
		INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
		INNER JOIN
			(
							SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
							UNION ALL
							SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
							UNION ALL
							SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
							UNION ALL
							SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
							UNION ALL
							SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
							UNION ALL
							SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
							UNION ALL
							SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
							UNION ALL
							SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
							UNION ALL
							SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
							UNION ALL
							SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
							UNION ALL
							SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
							UNION ALL
							SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
				) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
		LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
		INNER JOIN users u ON ccd.createdBy = u.id
		WHERE ccd.contractor_id = :contractor_id and cpr.isActive = 1
		GROUP BY ccd.contractor_id";

        $results = DB::select($sql, array(
            'contractor_id' => $contractorId
        ));

        return  count($results) > 0 ? $results[0] : [];
    }


    public function getAllSummaryByActionPlan($sorting = array(), $contractorId)
    {
        $query = "SELECT
				id
				,	source
				, classification
				, activities
				, cumplida
				, nocumplida
				, round((cumplida / activities) * 100, 2) advance
				, startDateTime
				, endDateTime
			from
			(
				select
					'SG-SST' source, pp.id, pp.`name` classification
					,count(*) activities
					, sum(case when p.`status` = 'abierto' then 1 else 0 end) nocumplida
					, sum(case when p.`status` = 'completado' then 1 else 0 end) cumplida
					, min(p.created_at) startDateTime
					, max(p.closeDateTime) endDateTime
				from
					wg_customer_diagnostic cd
                inner join wg_customers c on cd.customer_id = c.id
				inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
				inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
				inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
				inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
				inner join wg_progam_prevention pp on pp.id = ppc.program_id
				inner join wg_customer_diagnostic_prevention_action_plan p
						 on p.diagnostic_detail_id = cdp.id
				where cd.customer_id = :contractor_id_1
				group by pp.`name`
			) p

			UNION ALL

			select
				id
				,	source
				, classification
				, activities
				, cumplida
				, nocumplida
				, round((cumplida / activities) * 100, 2) advance
				, startDateTime
				, endDateTime
			from
			(
					select
							'Programas Empresariales' source, pm.id, pm.`name` classification
							,count(*) activities
							, sum(case when p.`status` = 'abierto' then 1 else 0 end) nocumplida
							, sum(case when p.`status` = 'completado' then 1 else 0 end) cumplida
							, min(p.created_at) startDateTime
							, max(p.closeDateTime) endDateTime
					from
						wg_customer_management_detail cmd
					inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
					inner join wg_customer_management cm on cm.id = cmd.management_id
					inner join wg_program_management_question q on q.id = cmd.question_id
					inner join wg_program_management_category c on c.id = q.category_id
					inner join wg_customer_management_program pg on pg.id = c.program_id
					inner join wg_program_management pm on pm.id = pg.program_id
					where cm.customer_id = :contractor_id_2
					group by pm.`name`
			) pm

			UNION ALL

			SELECT
				id
				,	source
				, classification
				, activities
				, cumplida
				, nocumplida
				, round((cumplida / activities) * 100, 2) advance
				, startDateTime
				, endDateTime
			FROM (
						SELECT
								'Contratistas' source,cpr.id, cpr.requirement classification
								,count(*) activities
								, sum(case when p.`status` = 'abierto' then 1 else 0 end) nocumplida
								, sum(case when p.`status` = 'completado' then 1 else 0 end) cumplida
								, min(p.created_at) startDateTime
								, max(p.closeDateTime) endDateTime
								, u.name
						FROM
								wg_customer_contract_detail ccd
						INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
						INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
						INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
						INNER JOIN users u ON ccd.createdBy = u.id
						WHERE cc.contractor_id = :contractor_id_3 and cpr.isActive = 1
						GROUP BY cpr.requirement
			) pc

			UNION ALL

			SELECT
				id
				,	source
				, classification
				, activities
				, cumplida
				, nocumplida
				, round((cumplida / activities) * 100, 2) advance
				, startDateTime
				, endDateTime
			FROM (
						SELECT
								'Matriz Riesgos' source,a.id, a.`name` classification
								,count(*) activities
								, sum(case when p.`status` = 'abierto' then 1 else 0 end) nocumplida
								, sum(case when p.`status` = 'completado' then 1 else 0 end) cumplida
								, min(p.created_at) startDateTime
								, max(p.closeDateTime) endDateTime
								, u.name
						FROM
							wg_customers cs
						INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
						inner join wg_customer_config_macro_process b on a.id = b.workplace_id
						inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
						inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
						inner join wg_customer_config_job_activity e on e.job_id = d.id
						inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
						inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
						inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
						INNER JOIN users u ON a.createdBy = u.id
						WHERE cs.id = :contractor_id_4 and a.`status` = 'Activo'
						GROUP BY a.id
			) pc

          UNION ALL

		  SELECT
				id
				,	source
				, classification
				, activities
				, cumplida
				, nocumplida
				, round((cumplida / activities) * 100, 2) advance
				, startDateTime
				, endDateTime
			FROM (
						SELECT
								'Ausentismo' source,cmd.category id, absenteeism_category.item COLLATE utf8_general_ci classification
								,count(*) activities
								, sum(case when p.`status` = 'abierto' then 1 else 0 end) nocumplida
								, sum(case when p.`status` = 'completado' then 1 else 0 end) cumplida
								, min(p.created_at) startDateTime
								, max(p.closeDateTime) endDateTime
								, u.name
						FROM
								wg_customer_absenteeism_disability cmd
						INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
						INNER JOIN  wg_customer_absenteeism_disability_action_plan p on cmd.id = p.customer_disability_id
						INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'absenteeism_category') absenteeism_category
							ON absenteeism_category.value = cmd.category
						INNER JOIN users u ON cmd.createdBy = u.id
						WHERE ce.customer_id = :contractor_id_5
						GROUP BY absenteeism_category.item
			) pa

			UNION ALL

            SELECT
				id
				,	source
				, classification
				, activities
				, cumplida
				, nocumplida
				, round((cumplida / activities) * 100, 2) advance
				, startDateTime
				, endDateTime
			FROM (
						SELECT
								'Investigación AT' source,i.accidentType COLLATE utf8_general_ci id, accident_type.item COLLATE utf8_general_ci classification
								,count(*) activities
								, sum(case when p.`status` = 'abierto' then 1 else 0 end) nocumplida
								, sum(case when p.`status` = 'completado' then 1 else 0 end) cumplida
								, min(p.created_at) startDateTime
								, max(p.closeDateTime) endDateTime
								, u.name
						FROM
								wg_customer_investigation_al i
						INNER JOIN  wg_customer_employee ce on i.customer_employee_id = ce.id
						INNER JOIN  wg_customer_investigation_al_measure m on i.id = m.customer_investigation_id
						INNER JOIN  wg_customer_investigation_al_measure_action_plan p on m.id = p.customer_investigation_measure_id
						INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'investigation_accident_type') accident_type
							ON accident_type.value COLLATE utf8_general_ci = i.accidentType
						INNER JOIN users u ON i.createdBy = u.id
						WHERE i.customer_id = :contractor_id_6
						GROUP BY accident_type.item
			) pi
			";

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId,
            'contractor_id_5' => $contractorId,
            'contractor_id_6' => $contractorId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivityDiagnostic($sorting = array(), $contractorId, $classificationId)
    {

        $query = "select
		'SG-SST' source, pp.id, pp.`name` classification
		, p.description
		, p.closeDateTime
		, p.`status`
		, p.id
		, resp.fullName
		, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
		, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
	from
		wg_customer_diagnostic cd
    inner join wg_customers c on cd.customer_id = c.id
	inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
	inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
	inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
	inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
	inner join wg_progam_prevention pp on pp.id = ppc.program_id
	inner join wg_customer_diagnostic_prevention_action_plan p
			 on p.diagnostic_detail_id = cdp.id
	left join (
							select
									 r.action_plan_id
									, r.id action_plan_resp_id
									, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
									, ct.customer_id
							from
							wg_contact ct
							left join (
														select * from system_parameters
														where system_parameters.group = 'rolescontact'
												) pr on ct.role = pr.value
							inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
					) resp on resp.action_plan_id = p.id
	where cd.customer_id = :contractor_id and pp.id = :program_id";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId,
            'program_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivityManagement($sorting = array(), $contractorId, $classificationId)
    {

        $query = "select
				'Programas Empresariales' source, pm.id, pm.`name` classification
			, p.description
			, p.closeDateTime
			, p.`status`
			, p.id
			, resp.fullName
			, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
			, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
		from
			wg_customer_management_detail cmd
		inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
		inner join wg_customer_management cm on cm.id = cmd.management_id
		inner join wg_program_management_question q on q.id = cmd.question_id
		inner join wg_program_management_category c on c.id = q.category_id
		inner join wg_customer_management_program pg on pg.id = c.program_id
		inner join wg_program_management pm on pm.id = pg.program_id
		left join (
								select
										 r.action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
		where cm.customer_id = :contractor_id and pm.id = :program_id";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId,
            'program_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivityContractor($sorting = array(), $contractorId, $classificationId)
    {

        $query = "SELECT
					'Contratistas' source,cpr.id, cpr.requirement classification
					, p.description
					, p.closeDateTime
					, p.`status`
					, p.id
					, resp.fullName
					, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
					, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
			FROM
					wg_customer_contract_detail ccd
			INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
			INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
			INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
			LEFT JOIN (
								select
										 r.contract_action_plan_id action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
			WHERE cc.contractor_id = :contractor_id and cpr.id = :program_id";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId,
            'program_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivityAbsenteeism($sorting = array(), $contractorId, $classificationId)
    {

        $query = "SELECT
					'Ausentismo' source, absenteeism_category.value id, absenteeism_category.item COLLATE utf8_general_ci classification
					, p.description
					, p.closeDateTime
					, p.`status`
					, p.id
					, resp.fullName
					, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
					, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
			FROM
					wg_customer_absenteeism_disability cmd
			INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
			INNER JOIN  wg_customer_absenteeism_disability_action_plan p on cmd.id = p.customer_disability_id
			INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'absenteeism_category') absenteeism_category
				ON absenteeism_category.value = cmd.category
			INNER JOIN users u ON cmd.createdBy = u.id
			LEFT JOIN (
								select
										 r.action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_absenteeism_disability_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
			WHERE ce.customer_id = :contractor_id and absenteeism_category.value = :program_id";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId,
            'program_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivityInvestigationAT($sorting = array(), $contractorId, $classificationId)
    {

        $query = "SELECT
		'Investigación AT' source, accident_type.value id, accident_type.item COLLATE utf8_general_ci classification
		, p.description
		, p.closeDateTime
		, p.`status`
		, p.id
		, resp.fullName
		, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
		, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
FROM
		wg_customer_investigation_al cmd
INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
INNER JOIN  wg_customer_investigation_al_measure m on cmd.id = m.customer_investigation_id
INNER JOIN  wg_customer_investigation_al_measure_action_plan p on m.id = p.customer_investigation_measure_id
INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'investigation_accident_type') accident_type
	ON accident_type.value COLLATE utf8_general_ci = cmd.accidentType
INNER JOIN users u ON cmd.createdBy = u.id
LEFT JOIN (
					select
							 r.action_plan_id
							, r.id action_plan_resp_id
							, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
							, ct.customer_id
					from
					wg_contact ct
					left join (
												select * from system_parameters
												where system_parameters.group = 'rolescontact'
										) pr on ct.role = pr.value
					inner join wg_customer_investigation_al_measure_action_plan_resp r on ct.id = r.contact_id
			) resp on resp.action_plan_id = p.id
WHERE ce.customer_id = :contractor_id and accident_type.value = :program_id
";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId,
            'program_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivityMatrix($sorting = array(), $contractorId, $classificationId)
    {

        $query = "SELECT
									'Matriz Riesgos' source,a.id, a.`name` classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName

									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customers cs
								INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
								inner join wg_customer_config_macro_process b on a.id = b.workplace_id
								inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
								inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
								inner join wg_customer_config_job_activity e on e.job_id = d.id
								inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
								inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
								inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
							LEFT JOIN (
												select
														 r.job_activity_hazard_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
			WHERE cs.id = :contractor_id and a.id = :program_id";

        $results = DB::select($query, array(
            'contractor_id' => $contractorId,
            'program_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanTaskDiagnostic($sorting = array(), $classificationId)
    {

        $query = "		select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status
						, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) agent
						,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pat.action_plan_id
		from wg_customer_diagnostic_prevention_action_plan_resp pa
		inner join wg_customer_diagnostic_prevention_action_plan_resp_task pat on pa.id = pat.action_plan_id
		inner join wg_contact ct on ct.id = pa.contact_id
		left join (
									select * from system_parameters
									where system_parameters.group = 'rolescontact'
							) pr on ct.role = pr.value
		left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
		where pat.action_plan_id = :action_plan_id
		order by id desc";

        //Log::info($query);
        //Log::info($classificationId);
        $results = DB::select($query, array(
            'action_plan_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanTaskManagement($sorting = array(), $classificationId)
    {

        $query = "		select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status
						, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) agent
						,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pat.action_plan_id
		from wg_customer_management_detail_action_plan_resp pa
		inner join wg_customer_management_detail_action_plan_resp_task pat on pa.id = pat.action_plan_id
		inner join wg_contact ct on ct.id = pa.contact_id
		left join (
									select * from system_parameters
									where system_parameters.group = 'rolescontact'
							) pr on ct.role = pr.value
		left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
		where pat.action_plan_id = :action_plan_id
		order by id desc";

        //Log::info($query);
        //Log::info($classificationId);
        $results = DB::select($query, array(
            'action_plan_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanTaskContractor($sorting = array(), $classificationId)
    {

        $query = "		select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status
						, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) agent
						,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pat.action_plan_id
		from wg_customer_contract_detail_action_plan_resp pa
		inner join wg_customer_contract_detail_action_plan_resp_task pat on pa.id = pat.action_plan_id
		inner join wg_contact ct on ct.id = pa.contact_id
		left join (
									select * from system_parameters
									where system_parameters.group = 'rolescontact'
							) pr on ct.role = pr.value
		left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
		where pat.action_plan_id = :action_plan_id
		order by id desc";

        //Log::info($query);
        //Log::info($classificationId);
        $results = DB::select($query, array(
            'action_plan_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanTaskAbsenteeism($sorting = array(), $classificationId)
    {

        $query = "select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status
						, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) agent
						,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pat.action_plan_id
		from wg_customer_absenteeism_disability_action_plan_resp pa
		inner join wg_customer_absenteeism_disability_action_plan_resp_task pat on pa.id = pat.action_plan_id
		inner join wg_contact ct on ct.id = pa.contact_id
		left join (
									select * from system_parameters
									where system_parameters.group = 'rolescontact'
							) pr on ct.role = pr.value
		left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
		where pat.action_plan_id = :action_plan_id
		order by id desc";

        //Log::info($query);
        //Log::info($classificationId);
        $results = DB::select($query, array(
            'action_plan_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanTaskInvestigationAT($sorting = array(), $classificationId)
    {

        $query = "select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status
						, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) agent
						,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pat.action_plan_id
		from wg_customer_investigation_al_measure_action_plan_resp pa
		inner join wg_customer_investigation_al_measure_action_plan_resp_task pat on pa.id = pat.action_plan_id
		inner join wg_contact ct on ct.id = pa.contact_id
		left join (
									select * from system_parameters
									where system_parameters.group = 'rolescontact'
							) pr on ct.role = pr.value
		left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
		where pat.action_plan_id = :action_plan_id
		order by id desc";

        //Log::info($query);
        //Log::info($classificationId);
        $results = DB::select($query, array(
            'action_plan_id' => $classificationId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanTaskMatrix($sorting = array(), $classificationId)
    {

        $query = "select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status
						, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) agent
						,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pat.job_activity_hazard_action_plan_resp_id action_plan_id
		from wg_customer_config_hazard_intervention_action_plan_resp pa
		inner join wg_customer_config_hazard_intervention_action_plan_resp_task pat on pa.id = pat.job_activity_hazard_action_plan_resp_id
		inner join wg_contact ct on ct.id = pa.contact_id
		left join (
									select * from system_parameters
									where system_parameters.group = 'rolescontact'
							) pr on ct.role = pr.value
		left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
		where pat.job_activity_hazard_action_plan_resp_id = :action_plan_id
		order by id desc";

        //Log::info($query);
        //Log::info($classificationId);
        $results = DB::select($query, array(
            'action_plan_id' => $classificationId
        ));

        return $results;
    }


    public function getAllSummaryByActionPlanActivities($search, $perPage = 10, $currentPage = 0, $audit = null, $contractorId = 0)
    {
        $query = "SELECT
				id
				,	source
				, classification
				, actionPlanId
				, description
				, closeDateTime
				, `status`
				, actionPlanRespId
				, fullName
				, isActive
		    from
			(
				select
						'SG-SST' source, pp.id, pp.`name` classification
						, p.description
						, p.closeDateTime
						, p.`status`
						, p.id actionPlanId
						, resp.fullName
						, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
						, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end actionPlanRespId
					from
						wg_customer_diagnostic cd
                    inner join wg_customers c on cd.customer_id = c.id
					inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
					inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
					inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
					inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
					inner join wg_progam_prevention pp on pp.id = ppc.program_id
					inner join wg_customer_diagnostic_prevention_action_plan p
							 on p.diagnostic_detail_id = cdp.id
					left join (
											select
													 r.action_plan_id
													, r.id action_plan_resp_id
													, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
													, ct.customer_id
											from
											wg_contact ct
											left join (
																		select * from system_parameters
																		where system_parameters.group = 'rolescontact'
																) pr on ct.role = pr.value
											inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
									) resp on resp.action_plan_id = p.id
					where cd.customer_id = :contractor_id_1
				union all
				select
								'Programas Empresariales' source, pm.id, pm.`name` classification
							, p.description
							, p.closeDateTime
							, p.`status`
							, p.id
							, resp.fullName
							, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
							, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
						from
							wg_customer_management_detail cmd
						inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
						inner join wg_customer_management cm on cm.id = cmd.management_id
						inner join wg_program_management_question q on q.id = cmd.question_id
						inner join wg_program_management_category c on c.id = q.category_id
						inner join wg_customer_management_program pg on pg.id = c.program_id
						inner join wg_program_management pm on pm.id = pg.program_id
						left join (
												select
														 r.action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
						where cm.customer_id = :contractor_id_2
				union all
				SELECT
									'Contratistas' source,cpr.id, cpr.requirement classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName
									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customer_contract_detail ccd
							INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
							INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
							INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
							LEFT JOIN (
												select
														 r.contract_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
							WHERE cc.contractor_id = :contractor_id_3

                UNION ALL

              SELECT
                        'Matriz Riesgos' source,a.id, a.`name` classification
                        , p.description
                        , p.closeDateTime
                        , p.`status`
                        , p.id
                        , resp.fullName
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
                FROM
                        wg_customers cs
                    INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
                    inner join wg_customer_config_macro_process b on a.id = b.workplace_id
                    inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
                    inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
                    inner join wg_customer_config_job_activity e on e.job_id = d.id
                    inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
                    inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
                    inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
                LEFT JOIN (
                                    select
                                             r.job_activity_hazard_action_plan_id action_plan_id
                                            , r.id action_plan_resp_id
                                            , CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
                                            , ct.customer_id
                                    from
                                    wg_contact ct
                                    left join (
                                                                select * from system_parameters
                                                                where system_parameters.group = 'rolescontact'
                                                        ) pr on ct.role = pr.value
                                    inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
                            ) resp on resp.action_plan_id = p.id
                WHERE cs.id = :contractor_id_4

              UNION ALL


            SELECT
					'Ausentismo' source, absenteeism_category.value id, absenteeism_category.item COLLATE utf8_general_ci classification
					, p.description
					, p.closeDateTime
					, p.`status`
					, p.id
					, resp.fullName
					, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
					, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
			FROM
					wg_customer_absenteeism_disability cmd
			INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
			INNER JOIN  wg_customer_absenteeism_disability_action_plan p on cmd.id = p.customer_disability_id
			INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'absenteeism_category') absenteeism_category
				ON absenteeism_category.value = cmd.category
			INNER JOIN users u ON cmd.createdBy = u.id
			LEFT JOIN (
								select
										 r.action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_absenteeism_disability_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
			WHERE ce.customer_id = :contractor_id_5

			) p";

        $startFrom = ($currentPage - 1) * $perPage;
        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.source";

        if ($audit != null) {
            $query .= $this->getWhere($audit->filters);
        }

        $query .= $orderBy . $limit;

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId,
            'contractor_id_5' => $contractorId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivitiesCount($search, $perPage = 10, $currentPage = 0, $audit = null, $contractorId = 0)
    {
        $query = "SELECT
				id
				,	source
				, classification
				, actionPlanId
				, description
				, closeDateTime
				, `status`
				, actionPlanRespId
				, fullName
				, isActive
		    from
			(
				select
						'SG-SST' source, pp.id, pp.`name` classification
						, p.description
						, p.closeDateTime
						, p.`status`
						, p.id actionPlanId
						, resp.fullName
						, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
						, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end actionPlanRespId
					from
						wg_customer_diagnostic cd
                    inner join wg_customers c on cd.customer_id = c.id
					inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
					inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
					inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
					inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
					inner join wg_progam_prevention pp on pp.id = ppc.program_id
					inner join wg_customer_diagnostic_prevention_action_plan p
							 on p.diagnostic_detail_id = cdp.id
					left join (
											select
													 r.action_plan_id
													, r.id action_plan_resp_id
													, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
													, ct.customer_id
											from
											wg_contact ct
											left join (
																		select * from system_parameters
																		where system_parameters.group = 'rolescontact'
																) pr on ct.role = pr.value
											inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
									) resp on resp.action_plan_id = p.id
					where cd.customer_id = :contractor_id_1
				union all
				select
								'Programas Empresariales' source, pm.id, pm.`name` classification
							, p.description
							, p.closeDateTime
							, p.`status`
							, p.id
							, resp.fullName
							, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
							, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
						from
							wg_customer_management_detail cmd
						inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
						inner join wg_customer_management cm on cm.id = cmd.management_id
						inner join wg_program_management_question q on q.id = cmd.question_id
						inner join wg_program_management_category c on c.id = q.category_id
						inner join wg_customer_management_program pg on pg.id = c.program_id
						inner join wg_program_management pm on pm.id = pg.program_id
						left join (
												select
														 r.action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
						where cm.customer_id = :contractor_id_2
				union all
				SELECT
									'Contratistas' source,cpr.id, cpr.requirement classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName
									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customer_contract_detail ccd
							INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
							INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
							INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
							LEFT JOIN (
												select
														 r.contract_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
							WHERE cc.contractor_id = :contractor_id_3

              UNION ALL

              SELECT
                        'Matriz Riesgos' source,a.id, a.`name` classification
                        , p.description
                        , p.closeDateTime
                        , p.`status`
                        , p.id
                        , resp.fullName
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
                FROM
                        wg_customers cs
                    INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
                    inner join wg_customer_config_macro_process b on a.id = b.workplace_id
                    inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
                    inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
                    inner join wg_customer_config_job_activity e on e.job_id = d.id
                    inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
                    inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
                    inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
                LEFT JOIN (
                                    select
                                             r.job_activity_hazard_action_plan_id action_plan_id
                                            , r.id action_plan_resp_id
                                            , CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
                                            , ct.customer_id
                                    from
                                    wg_contact ct
                                    left join (
                                                                select * from system_parameters
                                                                where system_parameters.group = 'rolescontact'
                                                        ) pr on ct.role = pr.value
                                    inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
                            ) resp on resp.action_plan_id = p.id
                WHERE cs.id = :contractor_id_4
			) p";

        $orderBy = " ORDER BY p.source";

        if ($audit != null) {
            $query .= $this->getWhere($audit->filters);
        }

        $query .= $orderBy;

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivitiesExport($audit = null, $contractorId = 0)
    {
        $query = "SELECT
				source `Origen`
				, classification `Clasificacion`
				, description `Actividad`
				, closeDateTime `Fecha Cierre`
				, fullName `Responsable`
				, `status` `Estado`
		    from
			(
				select
						'SG-SST' source, pp.id, pp.`name` classification
						, p.description
						, p.closeDateTime
						, p.`status`
						, p.id actionPlanId
						, resp.fullName
						, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
						, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end actionPlanRespId
					from
						wg_customer_diagnostic cd
                    inner join wg_customers c on cd.customer_id = c.id
					inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
					inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
					inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
					inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
					inner join wg_progam_prevention pp on pp.id = ppc.program_id
					inner join wg_customer_diagnostic_prevention_action_plan p
							 on p.diagnostic_detail_id = cdp.id
					left join (
											select
													 r.action_plan_id
													, r.id action_plan_resp_id
													, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
													, ct.customer_id
											from
											wg_contact ct
											left join (
																		select * from system_parameters
																		where system_parameters.group = 'rolescontact'
																) pr on ct.role = pr.value
											inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
									) resp on resp.action_plan_id = p.id
					where cd.customer_id = :contractor_id_1
				union all
				select
								'Programas Empresariales' source, pm.id, pm.`name` classification
							, p.description
							, p.closeDateTime
							, p.`status`
							, p.id
							, resp.fullName
							, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
							, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
						from
							wg_customer_management_detail cmd
						inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
						inner join wg_customer_management cm on cm.id = cmd.management_id
						inner join wg_program_management_question q on q.id = cmd.question_id
						inner join wg_program_management_category c on c.id = q.category_id
						inner join wg_customer_management_program pg on pg.id = c.program_id
						inner join wg_program_management pm on pm.id = pg.program_id
						left join (
												select
														 r.action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
						where cm.customer_id = :contractor_id_2
				union all
				SELECT
									'Contratistas' source,cpr.id, cpr.requirement classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName
									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customer_contract_detail ccd
							INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
							INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
							INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
							LEFT JOIN (
												select
														 r.contract_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
							WHERE cc.contractor_id = :contractor_id_3

            UNION ALL

              SELECT
                        'Matriz Riesgos' source,a.id, a.`name` classification
                        , p.description
                        , p.closeDateTime
                        , p.`status`
                        , p.id
                        , resp.fullName
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
                FROM
                        wg_customers cs
                    INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
                    inner join wg_customer_config_macro_process b on a.id = b.workplace_id
                    inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
                    inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
                    inner join wg_customer_config_job_activity e on e.job_id = d.id
                    inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
                    inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
                    inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
                LEFT JOIN (
                                    select
                                             r.job_activity_hazard_action_plan_id action_plan_id
                                            , r.id action_plan_resp_id
                                            , CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
                                            , ct.customer_id
                                    from
                                    wg_contact ct
                                    left join (
                                                                select * from system_parameters
                                                                where system_parameters.group = 'rolescontact'
                                                        ) pr on ct.role = pr.value
                                    inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
                            ) resp on resp.action_plan_id = p.id
                WHERE cs.id = :contractor_id_4

                UNION ALL

                SELECT
					'Ausentismo' source, absenteeism_category.value id, absenteeism_category.item COLLATE utf8_general_ci classification
					, p.description
					, p.closeDateTime
					, p.`status`
					, p.id
					, resp.fullName
					, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
					, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
			FROM
					wg_customer_absenteeism_disability cmd
			INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
			INNER JOIN  wg_customer_absenteeism_disability_action_plan p on cmd.id = p.customer_disability_id
			INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'absenteeism_category') absenteeism_category
				ON absenteeism_category.value = cmd.category
			INNER JOIN users u ON cmd.createdBy = u.id
			LEFT JOIN (
								select
										 r.action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_absenteeism_disability_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
			WHERE ce.customer_id = :contractor_id_5
			) p";

        $orderBy = " ORDER BY p.source";

        if ($audit != null) {
            $query .= $this->getWhere($audit->filters);
        }

        $query .= $orderBy;

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId,
            'contractor_id_5' => $contractorId,
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivitiesTask($search, $perPage = 10, $currentPage = 0, $audit = null, $contractorId = 0)
    {
        $query = "SELECT
				id
				,	source
				, classification
				, actionPlanId
				, description
				, task
				, closeDateTime
				, `status`
				, actionPlanRespId
				, fullName
				, isActive
		from
			(

				select
						'SG-SST' source, pp.id, pp.`name` classification
						, p.description
						, p.closeDateTime
						, p.`status`
						, p.id actionPlanId
						, resp.fullName
						, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
						, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
						, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end actionPlanRespId
					from
						wg_customer_diagnostic cd
                    inner join wg_customers c on cd.customer_id = c.id
					inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
					inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
					inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
					inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
					inner join wg_progam_prevention pp on pp.id = ppc.program_id
					inner join wg_customer_diagnostic_prevention_action_plan p
							 on p.diagnostic_detail_id = cdp.id
					left join (
											select
													 r.action_plan_id
													, r.id action_plan_resp_id
													, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
													, ct.customer_id
											from
											wg_contact ct
											left join (
																		select * from system_parameters
																		where system_parameters.group = 'rolescontact'
																) pr on ct.role = pr.value
											inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
									) resp on resp.action_plan_id = p.id
						 left join wg_customer_diagnostic_prevention_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
					where cd.customer_id = :contractor_id_1
				union all
				select
								'Programas Empresariales' source, pm.id, pm.`name` classification
							, p.description
							, p.closeDateTime
							, p.`status`
							, p.id
							, resp.fullName
							, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
							, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
							, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
						from
							wg_customer_management_detail cmd
						inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
						inner join wg_customer_management cm on cm.id = cmd.management_id
						inner join wg_program_management_question q on q.id = cmd.question_id
						inner join wg_program_management_category c on c.id = q.category_id
						inner join wg_customer_management_program pg on pg.id = c.program_id
						inner join wg_program_management pm on pm.id = pg.program_id
						left join (
												select
														 r.action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
							left join wg_customer_management_detail_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
						where cm.customer_id = :contractor_id_2
				union all
				SELECT
									'Contratistas' source,cpr.id, cpr.requirement classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName
									, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customer_contract_detail ccd
							INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
							INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
							INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
							LEFT JOIN (
												select
														 r.contract_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
								LEFT JOIN wg_customer_contract_detail_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
							WHERE cc.contractor_id = :contractor_id_3

                UNION ALL

                SELECT
                        'Matriz Riesgos' source,a.id, a.`name` classification
                        , p.description
                        , p.closeDateTime
                        , p.`status`
                        , p.id
                        , resp.fullName
                        , case when ISNULL(t.task) then 'Sin tareas' else t.task end task
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
                FROM
                        wg_customers cs
                    INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
                    inner join wg_customer_config_macro_process b on a.id = b.workplace_id
                    inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
                    inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
                    inner join wg_customer_config_job_activity e on e.job_id = d.id
                    inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
                    inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
                    inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
                LEFT JOIN (
                                    select
                                             r.job_activity_hazard_action_plan_id action_plan_id
                                            , r.id action_plan_resp_id
                                            , CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
                                            , ct.customer_id
                                    from
                                    wg_contact ct
                                    left join (
                                                                select * from system_parameters
                                                                where system_parameters.group = 'rolescontact'
                                                        ) pr on ct.role = pr.value
                                    inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
                            ) resp on resp.action_plan_id = p.id
                    LEFT JOIN wg_customer_config_hazard_intervention_action_plan_resp_task t on t.job_activity_hazard_action_plan_resp_id = resp.action_plan_resp_id
                WHERE cs.id = :contractor_id_4

                UNION ALL

SELECT
					'Ausentismo' source, absenteeism_category.value id, absenteeism_category.item COLLATE utf8_general_ci classification
					, p.description
					, p.closeDateTime
					, p.`status`
					, p.id
					, resp.fullName
					, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
					, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
					, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
			FROM
					wg_customer_absenteeism_disability cmd
			INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
			INNER JOIN  wg_customer_absenteeism_disability_action_plan p on cmd.id = p.customer_disability_id
			INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'absenteeism_category') absenteeism_category
				ON absenteeism_category.value = cmd.category
			INNER JOIN users u ON cmd.createdBy = u.id
			LEFT JOIN (
								select
										 r.action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_absenteeism_disability_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
			LEFT JOIN wg_customer_absenteeism_disability_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
			WHERE ce.customer_id = :contractor_id_5
			) p";

        $startFrom = ($currentPage - 1) * $perPage;
        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.source";

        if ($audit != null) {
            $query .= $this->getWhere($audit->filters);
        }

        $query .= $orderBy . $limit;

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId,
            'contractor_id_5' => $contractorId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivitiesTaskCount($search, $perPage = 10, $currentPage = 0, $audit = null, $contractorId = 0)
    {
        $query = "SELECT
				id
				,	source
				, classification
				, actionPlanId
				, description
				, task
				, closeDateTime
				, `status`
				, actionPlanRespId
				, fullName
				, isActive
		from
			(

				select
						'SG-SST' source, pp.id, pp.`name` classification
						, p.description
						, p.closeDateTime
						, p.`status`
						, p.id actionPlanId
						, resp.fullName
						, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
						, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
						, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end actionPlanRespId
					from
						wg_customer_diagnostic cd
                    inner join wg_customers c on cd.customer_id = c.id
					inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
					inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
					inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
					inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
					inner join wg_progam_prevention pp on pp.id = ppc.program_id
					inner join wg_customer_diagnostic_prevention_action_plan p
							 on p.diagnostic_detail_id = cdp.id
					left join (
											select
													 r.action_plan_id
													, r.id action_plan_resp_id
													, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
													, ct.customer_id
											from
											wg_contact ct
											left join (
																		select * from system_parameters
																		where system_parameters.group = 'rolescontact'
																) pr on ct.role = pr.value
											inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
									) resp on resp.action_plan_id = p.id
						 left join wg_customer_diagnostic_prevention_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
					where cd.customer_id = :contractor_id_1
				union all
				select
								'Programas Empresariales' source, pm.id, pm.`name` classification
							, p.description
							, p.closeDateTime
							, p.`status`
							, p.id
							, resp.fullName
							, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
							, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
							, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
						from
							wg_customer_management_detail cmd
						inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
						inner join wg_customer_management cm on cm.id = cmd.management_id
						inner join wg_program_management_question q on q.id = cmd.question_id
						inner join wg_program_management_category c on c.id = q.category_id
						inner join wg_customer_management_program pg on pg.id = c.program_id
						inner join wg_program_management pm on pm.id = pg.program_id
						left join (
												select
														 r.action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
							left join wg_customer_management_detail_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
						where cm.customer_id = :contractor_id_2
				union all
				SELECT
									'Contratistas' source,cpr.id, cpr.requirement classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName
									, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customer_contract_detail ccd
							INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
							INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
							INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
							LEFT JOIN (
												select
														 r.contract_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
								LEFT JOIN wg_customer_contract_detail_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
							WHERE cc.contractor_id = :contractor_id_3

              UNION ALL

                SELECT
                        'Matriz Riesgos' source,a.id, a.`name` classification
                        , p.description
                        , p.closeDateTime
                        , p.`status`
                        , p.id
                        , resp.fullName
                        , case when ISNULL(t.task) then 'Sin tareas' else t.task end task
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
                FROM
                        wg_customers cs
                    INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
                    inner join wg_customer_config_macro_process b on a.id = b.workplace_id
                    inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
                    inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
                    inner join wg_customer_config_job_activity e on e.job_id = d.id
                    inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
                    inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
                    inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
                LEFT JOIN (
                                    select
                                             r.job_activity_hazard_action_plan_id action_plan_id
                                            , r.id action_plan_resp_id
                                            , CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
                                            , ct.customer_id
                                    from
                                    wg_contact ct
                                    left join (
                                                                select * from system_parameters
                                                                where system_parameters.group = 'rolescontact'
                                                        ) pr on ct.role = pr.value
                                    inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
                            ) resp on resp.action_plan_id = p.id
                    LEFT JOIN wg_customer_config_hazard_intervention_action_plan_resp_task t on t.job_activity_hazard_action_plan_resp_id = resp.action_plan_resp_id
                WHERE cs.id = :contractor_id_4
			) p";

        $orderBy = " ORDER BY p.source";

        if ($audit != null) {
            $query .= $this->getWhere($audit->filters);
        }

        $query .= $orderBy;

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId
        ));

        return $results;
    }

    public function getAllSummaryByActionPlanActivitiesTaskExport($audit = null, $contractorId = 0)
    {
        $query = "SELECT
				source `Origen`
				, classification `Clasificacion`
				, description `Actividad`
				, task `Tarea`
				, closeDateTime `Fecha Cierre`
				, fullName `Responsable`
				, `status` `Estado`
		from
			(

				select
						'SG-SST' source, pp.id, pp.`name` classification
						, p.description
						, p.closeDateTime
						, p.`status`
						, p.id actionPlanId
						, resp.fullName
						, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
						, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
						, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end actionPlanRespId
					from
						wg_customer_diagnostic cd
                    inner join wg_customers c on cd.customer_id = c.id
					inner join wg_customer_diagnostic_prevention cdp on cd.id = cdp.diagnostic_id
					inner join wg_progam_prevention_question ppq on cdp.question_id = ppq.id
					inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id and ppqc.customer_size = c.size
					inner join wg_progam_prevention_category ppc on ppc.id = ppq.category_id
					inner join wg_progam_prevention pp on pp.id = ppc.program_id
					inner join wg_customer_diagnostic_prevention_action_plan p
							 on p.diagnostic_detail_id = cdp.id
					left join (
											select
													 r.action_plan_id
													, r.id action_plan_resp_id
													, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
													, ct.customer_id
											from
											wg_contact ct
											left join (
																		select * from system_parameters
																		where system_parameters.group = 'rolescontact'
																) pr on ct.role = pr.value
											inner join wg_customer_diagnostic_prevention_action_plan_resp r on ct.id = r.contact_id
									) resp on resp.action_plan_id = p.id
						 left join wg_customer_diagnostic_prevention_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
					where cd.customer_id = :contractor_id_1
				union all
				select
								'Programas Empresariales' source, pm.id, pm.`name` classification
							, p.description
							, p.closeDateTime
							, p.`status`
							, p.id
							, resp.fullName
							, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
							, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
							, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
						from
							wg_customer_management_detail cmd
						inner join  wg_customer_management_detail_action_plan p on cmd.id = p.management_detail_id
						inner join wg_customer_management cm on cm.id = cmd.management_id
						inner join wg_program_management_question q on q.id = cmd.question_id
						inner join wg_program_management_category c on c.id = q.category_id
						inner join wg_customer_management_program pg on pg.id = c.program_id
						inner join wg_program_management pm on pm.id = pg.program_id
						left join (
												select
														 r.action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_management_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
							left join wg_customer_management_detail_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
						where cm.customer_id = :contractor_id_2
				union all
				SELECT
									'Contratistas' source,cpr.id, cpr.requirement classification
									, p.description
									, p.closeDateTime
									, p.`status`
									, p.id
									, resp.fullName
									, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
									, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
									, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
							FROM
									wg_customer_contract_detail ccd
							INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
							INNER JOIN wg_customer_periodic_requirement cpr ON ccd.periodic_requirement_id = cpr.id
							INNER JOIN wg_customer_contract_detail_action_plan p on p.contract_detail_id = ccd.id
							LEFT JOIN (
												select
														 r.contract_action_plan_id action_plan_id
														, r.id action_plan_resp_id
														, CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
														, ct.customer_id
												from
												wg_contact ct
												left join (
																			select * from system_parameters
																			where system_parameters.group = 'rolescontact'
																	) pr on ct.role = pr.value
												inner join wg_customer_contract_detail_action_plan_resp r on ct.id = r.contact_id
										) resp on resp.action_plan_id = p.id
								LEFT JOIN wg_customer_contract_detail_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
							WHERE cc.contractor_id = :contractor_id_3

                UNION ALL

                SELECT
                        'Matriz Riesgos' source,a.id, a.`name` classification
                        , p.description
                        , p.closeDateTime
                        , p.`status`
                        , p.id
                        , resp.fullName
                        , case when ISNULL(t.task) then 'Sin tareas' else t.task end task
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
                        , case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
                FROM
                        wg_customers cs
                    INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
                    inner join wg_customer_config_macro_process b on a.id = b.workplace_id
                    inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
                    inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
                    inner join wg_customer_config_job_activity e on e.job_id = d.id
                    inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
                    inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
                    inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
                LEFT JOIN (
                                    select
                                             r.job_activity_hazard_action_plan_id action_plan_id
                                            , r.id action_plan_resp_id
                                            , CONCAT(ct.name,' ', ct.firstName, ' ', ct.lastName) fullName
                                            , ct.customer_id
                                    from
                                    wg_contact ct
                                    left join (
                                                                select * from system_parameters
                                                                where system_parameters.group = 'rolescontact'
                                                        ) pr on ct.role = pr.value
                                    inner join wg_customer_config_hazard_intervention_action_plan_resp r on ct.id = r.contact_id
                            ) resp on resp.action_plan_id = p.id
                    LEFT JOIN wg_customer_config_hazard_intervention_action_plan_resp_task t on t.job_activity_hazard_action_plan_resp_id = resp.action_plan_resp_id
                WHERE cs.id = :contractor_id_4


                UNION ALL

SELECT
					'Ausentismo' source, absenteeism_category.value id, absenteeism_category.item COLLATE utf8_general_ci classification
					, p.description
					, p.closeDateTime
					, p.`status`
					, p.id
					, resp.fullName
					, case when ISNULL(t.task) then 'Sin tareas' else t.task end task
					, case when ISNULL(resp.action_plan_resp_id) then 0 else 1 end isActive
					, case when ISNULL(resp.action_plan_resp_id) then 0 else resp.action_plan_resp_id end action_plan_resp_id
			FROM
					wg_customer_absenteeism_disability cmd
			INNER JOIN  wg_customer_employee ce on cmd.customer_employee_id = ce.id
			INNER JOIN  wg_customer_absenteeism_disability_action_plan p on cmd.id = p.customer_disability_id
			INNER JOIN (SELECT * FROM system_parameters where system_parameters.namespace = 'wgroup' and system_parameters.group = 'absenteeism_category') absenteeism_category
				ON absenteeism_category.value = cmd.category
			INNER JOIN users u ON cmd.createdBy = u.id
			LEFT JOIN (
								select
										 r.action_plan_id
										, r.id action_plan_resp_id
										, CONCAT(ct.firstName, ' ', ct.lastName,' ', ct.name) fullName
										, ct.customer_id
								from
								wg_contact ct
								left join (
															select * from system_parameters
															where system_parameters.group = 'rolescontact'
													) pr on ct.role = pr.value
								inner join wg_customer_absenteeism_disability_action_plan_resp r on ct.id = r.contact_id
						) resp on resp.action_plan_id = p.id
			LEFT JOIN wg_customer_absenteeism_disability_action_plan_resp_task t on t.action_plan_id = resp.action_plan_resp_id
			WHERE ce.customer_id = :contractor_id_5

			) p";

        $orderBy = " ORDER BY p.source";

        if ($audit != null) {
            $query .= $this->getWhere($audit->filters);
        }

        $query .= $orderBy;

        $results = DB::select($query, array(
            'contractor_id_1' => $contractorId,
            'contractor_id_2' => $contractorId,
            'contractor_id_3' => $contractorId,
            'contractor_id_4' => $contractorId,
            'contractor_id_5' => $contractorId,
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
