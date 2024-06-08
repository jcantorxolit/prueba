<?php

namespace Wgroup\CustomerConfigMinimumStandardItemDetail;

use DB;
use Exception;
use Log;
use Str;

class CustomerConfigMinimumStandardItemDetailService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

    function __construct()
    {
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
     * @param int $customerId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerId = 0)
    {

        $model = new CustomerConfigMinimumStandardItemDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerConfigMinimumStandardItemDetailRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_minimum_standard_item_detail.id',
            'wg_customer_config_minimum_standard_item_detail.customer_id',
            'wg_customer_config_minimum_standard_item_detail.minimum_standard_item_detail_id'
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
            $this->repository->sortBy('wg_customer_config_minimum_standard_item_detail.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_minimum_standard_item_detail.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_minimum_standard_item_detail.customer_id', $search);
            $filters[] = array('wg_customer_config_minimum_standard_item_detail.minimum_standard_item_detail_id', $search);
        }

        $this->repository->setColumns(['wg_customer_config_minimum_standard_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerConfigMinimumStandardItemDetail();
        $this->repository = new CustomerConfigMinimumStandardItemDetailRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_minimum_standard_item_detail.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_minimum_standard_item_detail.customer_id', $search);
            $filters[] = array('wg_customer_config_minimum_standard_item_detail.minimum_standard_item_detail_id', $search);
        }

        $this->repository->setColumns(['wg_customer_config_minimum_standard_item_detail.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function getAll($customerId, $minimumStandardItemId)
    {
        $query = "SELECT
	IFNULL(c.id,0) id,
	:customer_id_1 customerId,
	d.id minimumStandardItemDetailId,
	d.description,
	CASE WHEN c.id IS NULL THEN 0 ELSE 1 END isActive
FROM
	`wg_minimum_standard_item_detail` d
LEFT JOIN (
	SELECT
		*
	FROM
		wg_customer_config_minimum_standard_item_detail
	WHERE
		customer_id = :customer_id_2
) c ON d.id = c.minimum_standard_item_detail_id
WHERE
	type = 'verification-mode'
AND minimum_standard_item_id = :minimum_standard_item_id
ORDER BY d.id;";

        $result = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'minimum_standard_item_id' => $minimumStandardItemId,
        ));

        return $result;
    }
}
