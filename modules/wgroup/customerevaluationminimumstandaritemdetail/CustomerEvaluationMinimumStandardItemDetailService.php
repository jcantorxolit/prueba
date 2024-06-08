<?php

namespace Wgroup\CustomerEvaluationMinimumStandardItemDetail;

use DB;
use Exception;
use Log;
use Str;

class CustomerEvaluationMinimumStandardItemDetailService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

    function __construct()
    {
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array())
    {

        $model = new CustomerEvaluationMinimumStandardItemDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerEvaluationMinimumStandardItemDetailRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_evaluation_minimum_standard_item_detail.id',
            'wg_customer_evaluation_minimum_standard_item_detail.customer_evaluation_standard_minimum_item_id',
            'wg_customer_evaluation_minimum_standard_item_detail.minimum_standard_item_detail_id'
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
            $this->repository->sortBy('wg_customer_evaluation_minimum_standard_item_detail.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_evaluation_minimum_standard_item_detail.minimum_standard_item_detail_id', $search);
        }

        $this->repository->setColumns(['wg_customer_evaluation_minimum_standard_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new CustomerEvaluationMinimumStandardItemDetail();
        $this->repository = new CustomerEvaluationMinimumStandardItemDetailRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_evaluation_minimum_standard_item_detail.minimum_standard_item_detail_id', $search);
        }

        $this->repository->setColumns(['wg_customer_evaluation_minimum_standard_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function getAll($customerId, $customerEvaluationMinimumStandardItemId)
    {
        $query = "SELECT
	IFNULL(cemsid.id,0) id,
	c.customer_id customerId,
	cemsi.id customerEvaluationMinimumStandardItemId,
	d.description,
	CASE WHEN cemsid.id IS NULL THEN 0 ELSE 1 END isActive
FROM
	wg_customer_evaluation_minimum_standard_item cemsi
INNER JOIN
	wg_minimum_standard_item msi ON cemsi.minimum_standard_item_id = msi.id
INNER JOIN
	`wg_minimum_standard_item_detail` d on msi.id = d.minimum_standard_item_id
INNER JOIN (
	SELECT
		*
	FROM
		wg_customer_config_minimum_standard_item_detail
	WHERE
			customer_id = :customer_id
) c ON d.id = c.minimum_standard_item_detail_id
LEFT JOIN wg_customer_evaluation_minimum_standard_item_detail cemsid ON d.id = c.minimum_standard_item_detail_id
WHERE
	d.type = 'verification-mode'
AND cemsi.id = :customer_evaluation_minimum_standard_item_id
ORDER BY d.id;";

        $result = DB::select($query, array(
            'customer_id' => $customerId,
            'customer_evaluation_minimum_standard_item_id' => $customerEvaluationMinimumStandardItemId,
        ));

        return $result;
    }
}
