<?php

namespace Wgroup\CustomerRoadSafetyItemComment;

use DB;
use Exception;
use Log;
use Str;

class CustomerRoadSafetyItemCommentService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerRoadSafetyItemId)
    {

        $model = new CustomerRoadSafetyItemComment();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerRoadSafetyItemCommentRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_road_safety_item_comment.id',
            'wg_customer_road_safety_item_comment.customer_road_safety_item_id',
            'wg_customer_road_safety_item_comment.comment'
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
            $this->repository->sortBy('wg_customer_road_safety_item_comment.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_road_safety_item_comment.customer_road_safety_item_id', $customerRoadSafetyItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_road_safety_item_comment.comment', $search);
            $filters[] = array('users.name', $search);
        }

        $this->repository->setColumns(['wg_customer_road_safety_item_comment.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerRoadSafetyItemId)
    {

        $model = new CustomerRoadSafetyItemComment();
        $this->repository = new CustomerRoadSafetyItemCommentRepository($model);

        $filters[] = array('wg_customer_road_safety_item_comment.customer_road_safety_item_id', $customerRoadSafetyItemId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_road_safety_item_comment.comment', $search);
            $filters[] = array('users.name', $search);
        }


        $this->repository->setColumns(['wg_customer_road_safety_item_comment.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $customerRoadSafetyItemId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT *
FROM
  (SELECT pc.id,
          pc.diagnostic_detail_id,
          pc.`comment`,
          u.`name` `user`,
          pc.created_at createdAt
   FROM wg_customer_road_safety_item_comment_comment pc
   INNER JOIN users u ON pc.createdBy = u.id
   WHERE pc.customer_road_safety_item_id = :customer_road_safety_item_id) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.comment like '%$search%' or p.name like '%$search%' or p.createdAt like '%$search%')";
        }

        $query .= $where;

        $order = " ORDER BY p.createdAt DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            'customer_road_safety_item_id' => $customerRoadSafetyItemId
        ));

        return $results;

    }

    public function getAllCount($search = "", $customerRoadSafetyItemId = 0)
    {

        $query = "SELECT *
FROM
  (SELECT pc.id,
          pc.diagnostic_detail_id,
          pc.`comment`,
          u.`name` `user`,
          pc.created_at createdAt
   FROM wg_customer_road_safety_item_comment_comment pc
   INNER JOIN users u ON pc.createdBy = u.id
   WHERE pc.customer_road_safety_item_id = :customer_road_safety_item_id) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.comment like '%$search%' or p.name like '%$search%' or p.createdAt like '%$search%')";
        }

        $query .= $where;

        $order = " ORDER BY p.createdAt DESC ";

        $query .= $order;

        $results = DB::select($query, array(
            'customer_road_safety_item_id' => $customerRoadSafetyItemId
        ));

        return count($results);
    }
}
