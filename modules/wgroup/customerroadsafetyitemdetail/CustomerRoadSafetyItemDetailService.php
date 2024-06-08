<?php

namespace Wgroup\CustomerRoadSafetyItemDetail;

use DB;
use Exception;
use Log;
use Str;

class CustomerRoadSafetyItemDetailService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerRoadSafetyItemId = 0)
    {

        $model = new CustomerRoadSafetyItemDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerRoadSafetyItemDetailRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_road_safety_item_detail.id',
            'wg_customer_road_safety_item_detail.customer_road_safety_item_id',
            'wg_customer_road_safety_item_detail.road_safety_item_detail_id'
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
            $this->repository->sortBy('wg_customer_road_safety_item_detail.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_road_safety_item_detail.road_safety_item_detail_id', $search);
        }

        $this->repository->setColumns(['wg_customer_road_safety_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerRoadSafetyItemId = 0)
    {

        $model = new CustomerRoadSafetyItemDetail();
        $this->repository = new CustomerRoadSafetyItemDetailRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_road_safety_item_detail.road_safety_item_detail_id', $search);
        }

        $this->repository->setColumns(['wg_customer_road_safety_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function getAll($customerId, $customerRoadSafetyItemId)
    {
        $query = "SELECT
	IFNULL(B.customer_road_safety_item_detail_id, 0) id,
	A.customer_id customerId,
	$customerRoadSafetyItemId customerRoadSafetyItemId,
	A.road_safety_item_detail_id minimumStandardItemDetailId,
	A.description,
		CASE
	WHEN B.customer_road_safety_item_detail_id IS NULL THEN
		0
	ELSE
		1
	END isActive
FROM
	(
		SELECT
			c.customer_id,
			msi.id AS road_safety_item_id,
			d.id AS road_safety_item_detail_id,
			d.description
		FROM
			wg_road_safety_item msi -- ON cemsi.road_safety_item_id = msi.id
		INNER JOIN `wg_road_safety_item_detail` d ON msi.id = d.road_safety_item_id
		INNER JOIN (
			SELECT
				*
			FROM
				wg_customer_config_road_safety_item_detail
			WHERE
				customer_id = :customer_id
		) c ON d.id = c.road_safety_item_detail_id
		WHERE
			d.type = 'verification-mode' AND msi.id  IN (select road_safety_item_id from wg_customer_road_safety_item where id = :customer_road_safety_item_id_1)
	) A
LEFT JOIN (
	SELECT
		cems.id AS customer_road_safety,
		cemsi.id AS customer_road_safety_item_id,
		cemsi.road_safety_item_id,
		cemsid.id AS customer_road_safety_item_detail_id,
		cemsid.road_safety_item_detail_id
	FROM
		wg_customer_road_safety cems
	INNER JOIN wg_customer_road_safety_item cemsi ON cems.id = cemsi.customer_road_safety_id
	LEFT JOIN wg_customer_road_safety_item_detail cemsid ON cemsi.id = cemsid.customer_road_safety_item_id
	WHERE
		cemsi.id = :customer_road_safety_item_id_2
    GROUP BY cemsid.road_safety_item_detail_id, cemsi.id
) B ON A.road_safety_item_detail_id = B.road_safety_item_detail_id -- select * from wg_customer_road_safety_item_detail
";

        $result = DB::select($query, array(
            'customer_id' => $customerId,
            'customer_road_safety_item_id_1' => $customerRoadSafetyItemId,
            'customer_road_safety_item_id_2' => $customerRoadSafetyItemId
        ));

        return $result;
    }

    public function getAllQuestion($customerRoadSafetyItemId)
    {
        $sql = "select msiq.id
	, pq.description
	, pq.article
	, wr.text as rate
from wg_customer_road_safety ems
inner join wg_customer_road_safety_item emsi on ems.id = emsi.customer_road_safety_id
inner join wg_road_safety_item msi on emsi.road_safety_item_id = msi.id
inner join wg_road_safety_item_question msiq on msi.id = msiq.road_safety_item_id
inner join (select MAX(id) id, customer_id from wg_customer_diagnostic group by customer_id) cd on cd.customer_id = ems.customer_id
INNER JOIN wg_customer_diagnostic_prevention dp ON dp.question_id = msiq.program_prevention_question_id AND dp.diagnostic_id = cd.id
INNER JOIN wg_progam_prevention_question pq ON dp.question_id = pq.id
LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
where emsi.id = :id";

        $results = DB::select($sql, array(
            'id' => $customerRoadSafetyItemId
        ));

        return $results;
    }
}
