<?php

namespace Wgroup\CustomerMatrix;

use DB;
use Exception;
use Log;
use Str;


class CustomerMatrixService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerRepository();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId)
    {

        $model = new CustomerMatrix();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerMatrixRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_matrix.description',
            'wg_customer_matrix.status'
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
                    $this->customerConfigWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_matrix.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_matrix.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_matrix.description', $search);
            $filters[] = array('config_matrix_status.item', $search);
            $filters[] = array('wg_matrix_type.item', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_matrix.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerMatrix();
        $this->customerConfigWorkPlaceRepository = new CustomerMatrixRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_matrix.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_matrix.description', $search);
            $filters[] = array('config_matrix_status.item', $search);
            $filters[] = array('wg_matrix_type.item', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_matrix.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }

    public function getSummary($customerMatrixId = 0) {


        $query = "SELECT * FROM
(
SELECT o.id ,
       'PROYECTOS' `name` ,
                   IFNULL(d.qty ,0) created ,
                   IFNULL(a.qty ,0) configured ,
                   IFNULL(d.qty, 0) - IFNULL(a.qty ,0)  pending
FROM wg_customer_matrix o
LEFT JOIN
  (SELECT count(*) qty,
          customer_matrix_id
   FROM wg_customer_matrix_project
   GROUP BY customer_matrix_id) d ON o.id = d.customer_matrix_id
LEFT JOIN
	(SELECT count(*) qty,
					customer_matrix_id,
          customer_matrix_project_id
   FROM wg_customer_matrix_data
   GROUP BY customer_matrix_id, customer_matrix_project_id) a ON o.id = a.customer_matrix_id
GROUP BY o.id

UNION ALL

SELECT o.id ,
       'ACTIVIDADES' `name` ,
                   IFNULL(d.qty ,0) created ,
                   IFNULL(a.qty ,0) configured ,
                   IFNULL(d.qty, 0) - IFNULL(a.qty ,0)  pending
FROM wg_customer_matrix o
LEFT JOIN
  (SELECT count(*) qty,
          customer_matrix_id
   FROM wg_customer_matrix_activity
   GROUP BY customer_matrix_id) d ON o.id = d.customer_matrix_id
LEFT JOIN
	(SELECT count(*) qty,
					customer_matrix_id,
          customer_matrix_activity_id
   FROM wg_customer_matrix_data
   GROUP BY customer_matrix_id, customer_matrix_activity_id) a ON o.id = a.customer_matrix_id
GROUP BY o.id

UNION ALL

SELECT o.id ,
       'IMPACTOS AMBIENTALES' `name` ,
                   IFNULL(d.qty ,0) created ,
                   IFNULL(a.qty ,0) configured ,
                   IFNULL(d.qty, 0) - IFNULL(a.qty ,0)  pending
FROM wg_customer_matrix o
LEFT JOIN
  (SELECT count(*) qty,
          customer_matrix_id
   FROM wg_customer_matrix_environmental_impact
   GROUP BY customer_matrix_id) d ON o.id = d.customer_matrix_id
LEFT JOIN
	(SELECT count(*) qty,
					customer_matrix_id,
          customer_matrix_environmental_impact_id
   FROM wg_customer_matrix_data
   GROUP BY customer_matrix_id, customer_matrix_environmental_impact_id) a ON o.id = a.customer_matrix_id
GROUP BY o.id

UNION ALL

SELECT o.id ,
       'ASPECTOS AMBIENTALES' `name` ,
                   IFNULL(d.qty ,0) created ,
                   IFNULL(a.qty ,0) configured ,
                   IFNULL(d.qty, 0) - IFNULL(a.qty ,0)  pending
FROM wg_customer_matrix o
LEFT JOIN
  (SELECT count(*) qty,
          customer_matrix_id
   FROM wg_customer_matrix_environmental_aspect
   GROUP BY customer_matrix_id) d ON o.id = d.customer_matrix_id
LEFT JOIN
	(SELECT count(*) qty,
					customer_matrix_id,
          customer_matrix_environmental_aspect_id
   FROM wg_customer_matrix_data
   GROUP BY customer_matrix_id, customer_matrix_environmental_aspect_id) a ON o.id = a.customer_matrix_id
GROUP BY o.id

) p
where id = :id;";


        $results = DB::select( $query, array(
            'id' => $customerMatrixId
        ));

        return $results;

    }
}
