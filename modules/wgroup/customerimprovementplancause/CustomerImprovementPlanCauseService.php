<?php

namespace Wgroup\CustomerImprovementPlanCause;

use DB;
use Exception;
use Log;
use Str;

class CustomerImprovementPlanCauseService
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
     * @param string $customerImprovementPlanId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanCause();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerImprovementPlanCauseRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_cause.id',
            'wg_customer_improvement_plan_cause.created_at',
            'users.name',
            'wg_improvement_plan_cause_category.name',
            'wg_customer_improvement_plan_cause_root_cause.cause',
            'wg_customer_improvement_plan_cause_root_cause.factor',
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
            $this->customerContractorRepository->sortBy('wg_customer_improvement_plan_cause.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_cause.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_cause.created_at', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.factor', $search);
            $filters[] = array('users.name', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_improvement_plan_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerImprovementPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanCause();
        $this->customerContractorRepository = new CustomerImprovementPlanCauseRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_cause.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_cause.created_at', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.factor', $search);
            $filters[] = array('users.name', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_improvement_plan_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getCauses($id)
    {
        $sql = "SELECT
	pc.id,
	UPPER(pcc.`name`) `name`
FROM
	wg_customer_improvement_plan p
INNER JOIN wg_customer_improvement_plan_cause pc ON p.id = pc.customer_improvement_plan_id
INNER JOIN wg_improvement_plan_cause_category pcc ON pc.cause = pcc.id
WHERE
	p.id = :id";

        $result = DB::select($sql, array(
            'id' => $id
        ));

        return $result;
    }

    public function getSubCauses($id)
    {
        $sql = "SELECT
	pc.id,
	c.`name` `name`
FROM
	wg_customer_improvement_plan p
INNER JOIN wg_customer_improvement_plan_cause pc ON p.id = pc.customer_improvement_plan_id
INNER JOIN wg_improvement_plan_cause_category pcc ON pc.cause = pcc.id
INNER JOIN wg_customer_improvement_plan_cause_sub_cause csc ON pc.id = csc.customer_improvement_plan_cause_id
INNER JOIN wg_improvement_plan_cause c ON csc.cause = c.id
WHERE
	p.id = :id";

        $result = DB::select($sql, array(
            'id' => $id
        ));

        return $result;

    }

    public function getRootCauses($id)
    {
        $sql = "SELECT
	pc.id,
	crc.`cause` `name`
FROM
	wg_customer_improvement_plan p
INNER JOIN wg_customer_improvement_plan_cause pc ON p.id = pc.customer_improvement_plan_id
INNER JOIN wg_improvement_plan_cause_category pcc ON pc.cause = pcc.id
INNER JOIN wg_customer_improvement_plan_cause_root_cause crc ON pc.id = crc.customer_improvement_plan_cause_id
WHERE
	p.id = :id";

        $result = DB::select($sql, array(
            'id' => $id
        ));

        return $result;
    }
}
