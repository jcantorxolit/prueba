<?php

namespace Wgroup\CustomerContractorSafetyInspection;

use DB;
use Exception;
use Log;
use Str;

class CustomerContractorSafetyInspectionService
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

        $model = new CustomerContractorSafetyInspection();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerContractorSafetyInspectionRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_safety_inspection.id',
            'wg_customer_safety_inspection.description',
            'wg_customer_safety_inspection.reason',
            'wg_customer_safety_inspection.date',
            'wg_customer_safety_inspection.version',
            'wg_customer_safety_inspection.isActive'
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
            $this->customerContractorRepository->sortBy('wg_customer_safety_inspection.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_safety_inspection.id', $search);
            $filters[] = array('wg_customer_safety_inspection.description', $search);
            $filters[] = array('wg_customer_safety_inspection.reason', $search);
            $filters[] = array('wg_customer_safety_inspection.date', $search);
            $filters[] = array('wg_customer_safety_inspection.version', $search);
            $filters[] = array('wg_customer_safety_inspection.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_safety_inspection.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_safety_inspection.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerContractorSafetyInspection();
        $this->customerContractorRepository = new CustomerContractorSafetyInspectionRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_safety_inspection.id', $search);
            $filters[] = array('wg_customer_safety_inspection.description', $search);
            $filters[] = array('wg_customer_safety_inspection.reason', $search);
            $filters[] = array('wg_customer_safety_inspection.date', $search);
            $filters[] = array('wg_customer_safety_inspection.version', $search);
            $filters[] = array('wg_customer_safety_inspection.isActive', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllSummaryBy($sorting = array(), $safetyInspectionId)
    {

        $columnNames = ["id", "questions", "answers", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "SELECT list.id, name, questions , answers, round((answers / questions) * 100, 2) advance, round((total / questions), 2) average, total
FROM
		(
						SELECT  csicl.id, csicl.`name`, count(*) questions
							, SUM(CASE WHEN csilip.dangerousness = 0 AND csilip.existingControl = 0  THEN 0 ELSE 1 END) answers
							, SUM(csilip.dangerousness * existingControl) total
						FROM
							wg_customer_safety_inspection csi
						INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
						INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
						INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
						INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
						INNER JOIN wg_customer_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
						LEFT JOIN (
												SELECT csili.*,
															IFNULL(csiclv_d.`value`,0) dangerousness,
															IFNULL(csiclv_p.`value`,0) priority,
															IFNULL(csiclv_e.`value`,0) existingControl
												FROM
													wg_customer_safety_inspection csi
												INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
												INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
												INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
												INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
												INNER JOIN wg_customer_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_d on csiclv_d.customer_safety_inspection_config_list_id = csicl.id and csili.dangerousnessValue = csiclv_d.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_p on csiclv_p.customer_safety_inspection_config_list_id = csicl.id and csili.priorityValue = csiclv_p.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_e on csiclv_e.customer_safety_inspection_config_list_id = csicl.id and csili.existingControlValue = csiclv_e.id
												WHERE customer_safety_inspection_id = :customer_safety_inspection_id_1
								) csilip on csilip.customer_safety_inspection_config_list_item_id = csili.customer_safety_inspection_config_list_item_id
						WHERE csi.id = :customer_safety_inspection_id_2 AND csicl.isActive = 1 AND csiclg.isActive = 1 AND csicli.isActive = 1
						GROUP BY  csicl.`name`, csicl.id
		) list";

        $results = DB::select($query, array(
            'customer_safety_inspection_id_1' => $safetyInspectionId,
            'customer_safety_inspection_id_2' => $safetyInspectionId
        ));

        return $results;
    }

    public function getAllSummaryExport($sorting = array(), $safetyInspectionId)
    {

        $columnNames = ["id", "questions", "answers", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "SELECT name AS Lista, questions AS Preguntas, answers AS Respuestas, round((answers / questions) * 100, 2) AS `Avance (%)`, round((total / questions), 2) AS `Promedio Total (%)`, total AS `Total`
FROM
		(
						SELECT  csicl.id, csicl.`name`, count(*) questions
							, SUM(CASE WHEN csilip.dangerousness = 0 AND csilip.existingControl = 0  THEN 0 ELSE 1 END) answers
							, SUM(csilip.dangerousness * existingControl) total
						FROM
							wg_customer_safety_inspection csi
						INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
						INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
						INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
						INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
						INNER JOIN wg_customer_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
						LEFT JOIN (
												SELECT csili.*,
															IFNULL(csiclv_d.`value`,0) dangerousness,
															IFNULL(csiclv_p.`value`,0) priority,
															IFNULL(csiclv_e.`value`,0) existingControl
												FROM
													wg_customer_safety_inspection csi
												INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
												INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
												INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
												INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
												INNER JOIN wg_customer_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_d on csiclv_d.customer_safety_inspection_config_list_id = csicl.id and csili.dangerousnessValue = csiclv_d.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_p on csiclv_p.customer_safety_inspection_config_list_id = csicl.id and csili.priorityValue = csiclv_p.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_e on csiclv_e.customer_safety_inspection_config_list_id = csicl.id and csili.existingControlValue = csiclv_e.id
												WHERE customer_safety_inspection_id = :customer_safety_inspection_id_1
								) csilip on csilip.customer_safety_inspection_config_list_item_id = csili.customer_safety_inspection_config_list_item_id
						WHERE csi.id = :customer_safety_inspection_id_2 AND csicl.isActive = 1 AND csiclg.isActive = 1 AND csicli.isActive = 1
						GROUP BY  csicl.`name`, csicl.id
		) list";

        $results = DB::select($query, array(
            'customer_safety_inspection_id_1' => $safetyInspectionId,
            'customer_safety_inspection_id_2' => $safetyInspectionId
        ));

        return $results;
    }

    public static function bulkInsert($customerId)
    {
        $query = "insert into wg_customer_contractor_safety_inspection
                  SELECT null as id, csi.id as customer_safety_inspection_id, cc.contractor_id, 1 as active, 2 as createBy, 2 as updatedBy, NOW() as created_at, NOW() as updated_at FROM `wg_customer_safety_inspection` csi
                      inner JOIN wg_customer_contractor cc
	                    on cc.customer_id = csi.customer_id
	                      and cc.contractor_type_id = csi.contractorType
                      left join wg_customer_contractor_safety_inspection ccsi
	                    on ccsi.customer_safety_inspection_id  = csi.id
	                      and ccsi.customer_id = cc.contractor_id
where cc.customer_id = :customer_id  and ccsi.id is NULL;";


        DB::statement($query, array(
            'customer_id' => $customerId
        ));
    }

    public static function bulkUpdate($customerId, $contractType,$isActive = 1)
    {
        $query = "update wg_customer_contractor_safety_inspection cssi
                  inner join `wg_customer_safety_inspection` csi
	                on cssi.customer_safety_inspection_id  = csi.id
                  inner JOIN wg_customer_contractor cc
                    on cc.customer_id = csi.customer_id and cc.contractor_type_id = csi.contractorType
                  set cssi.isActive = :is_active
                  where cc.customer_id = :customer_id and csi.contractorType = :contract_type";


        DB::statement($query, array(
            'customer_id' => $customerId,
            'contract_type' => $contractType,
            'is_active' => $isActive

        ));
    }
}
