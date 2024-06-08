<?php

namespace Wgroup\CustomerContractorSafetyInspectionListItem;

use DB;
use Exception;
use Log;
use Str;

class CustomerContractorSafetyInspectionListItemService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerSafetyInspectionConfigListId = 0) {

        $model = new CustomerContractorSafetyInspectionListItem();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerContractorSafetyInspectionListItemRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_safety_inspection_list_item.id',
            'wg_customer_safety_inspection_list_item.description',
            'wg_customer_safety_inspection_list_item.isActive'
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
            $this->customerContractorRepository->sortBy('wg_customer_safety_inspection_list_item.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection_config_list.id', $customerSafetyInspectionConfigListId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_safety_inspection_list_item.id', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item.description', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_safety_inspection_list_item.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_safety_inspection_list_item.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection_list_item.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerSafetyInspectionConfigListId) {

        $model = new CustomerContractorSafetyInspectionListItem();
        $this->customerContractorRepository = new CustomerContractorSafetyInspectionListItemRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection_config_list.id', $customerSafetyInspectionConfigListId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_safety_inspection_list_item.id', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item.description', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item.isActive', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection_list_item.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getLists($customerContractorId)
    {
        $query = "SELECT list.id, name, customerSafetyInspectionId, questions , answers, round((answers / questions) * 100, 2) advance, round((total / questions), 2) average, total
FROM
		(
						SELECT  csicl.id, csicl.`name`, count(*) questions, csi.id customerSafetyInspectionId
							, SUM(CASE WHEN csilip.dangerousness = 0 AND csilip.existingControl = 0  THEN 0 ELSE 1 END) answers
							, SUM(csilip.dangerousness * existingControl) total
						FROM
							wg_customer_safety_inspection csi
						INNER JOIN wg_customer_contractor cc
							on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
						INNER JOIN wg_customer_contractor_safety_inspection ccsi
							on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
						INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
						INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
						INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
						INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
						INNER JOIN wg_customer_contractor_safety_inspection_list_item csili
							on csili.customer_safety_inspection_list_id = csil.id
								and csili.customer_safety_inspection_config_list_item_id = csicli.id
						LEFT JOIN (
												SELECT csili.*,
															IFNULL(csiclv_d.`value`,0) dangerousness,
															IFNULL(csiclv_p.`value`,0) priority,
															IFNULL(csiclv_e.`value`,0) existingControl
												FROM
													wg_customer_safety_inspection csi
													INNER JOIN wg_customer_contractor cc
														on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
													INNER JOIN wg_customer_contractor_safety_inspection ccsi
														on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
												INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
												INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
												INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
												INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
												INNER JOIN wg_customer_contractor_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_d on csiclv_d.customer_safety_inspection_config_list_id = csicl.id and csili.dangerousnessValue = csiclv_d.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_p on csiclv_p.customer_safety_inspection_config_list_id = csicl.id and csili.priorityValue = csiclv_p.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_e on csiclv_e.customer_safety_inspection_config_list_id = csicl.id and csili.existingControlValue = csiclv_e.id
												WHERE cc.id = :customer_contractor_id_1
								) csilip on csilip.customer_safety_inspection_config_list_item_id = csili.customer_safety_inspection_config_list_item_id
						WHERE cc.id = :customer_contractor_id_2 AND csicl.isActive = 1 AND csiclg.isActive = 1 AND csicli.isActive = 1
						GROUP BY  csicl.`name`, csicl.id
		) list";

        $results = DB::select( $query, array(
            'customer_contractor_id_1' => $customerContractorId,
            'customer_contractor_id_2' => $customerContractorId
        ));

        return $results;
    }

    public function getListGroups($customerContractorId)
    {
        $query = "SELECT list.id, description, customerSafetyInspectionConfigListId, answers, round((answers / questions) * 100, 2) advance, round((total / questions), 2) average, total
FROM
		(
						SELECT  csiclg.id, csiclg.`description`, count(*) questions, csicl.id customerSafetyInspectionConfigListId
							, SUM(CASE WHEN csilip.dangerousness = 0 AND csilip.existingControl = 0  THEN 0 ELSE 1 END) answers
							, SUM(csilip.dangerousness * existingControl) total, csiclg.sort
						FROM
							wg_customer_safety_inspection csi
						INNER JOIN wg_customer_contractor cc
							on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
						INNER JOIN wg_customer_contractor_safety_inspection ccsi
							on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
						INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
						INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
						INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
						INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
						INNER JOIN wg_customer_contractor_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
						LEFT JOIN (
												SELECT csili.*,
															IFNULL(csiclv_d.`value`,0) dangerousness,
															IFNULL(csiclv_p.`value`,0) priority,
															IFNULL(csiclv_e.`value`,0) existingControl
												FROM
													wg_customer_safety_inspection csi
												INNER JOIN wg_customer_contractor cc
													on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
												INNER JOIN wg_customer_contractor_safety_inspection ccsi
													on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
												INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
												INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
												INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
												INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
												INNER JOIN wg_customer_contractor_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_d on csiclv_d.customer_safety_inspection_config_list_id = csicl.id and csili.dangerousnessValue = csiclv_d.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_p on csiclv_p.customer_safety_inspection_config_list_id = csicl.id and csili.priorityValue = csiclv_p.id
												LEFT JOIN wg_customer_safety_inspection_config_list_validation csiclv_e on csiclv_e.customer_safety_inspection_config_list_id = csicl.id and csili.existingControlValue = csiclv_e.id
												WHERE cc.id = :customer_contractor_id_1
								) csilip on csilip.customer_safety_inspection_config_list_item_id = csili.customer_safety_inspection_config_list_item_id
						WHERE cc.id = :customer_contractor_id_2 AND csicl.isActive = 1 AND csiclg.isActive = 1 AND csicli.isActive = 1
						GROUP BY  csiclg.`description`, csiclg.id
		) list
ORDER BY list.sort";

        $results = DB::select( $query, array(
            'customer_contractor_id_1' => $customerContractorId,
            'customer_contractor_id_2' => $customerContractorId
        ));

        return $results;
    }

    public function getListItems($customerContractorId, $action = null)
    {
        $andWhere = $action && trim($action) != "" ? " AND csili.action = '$action'" : "";

        $query = "SELECT
        csili.id,
        csili.observation,
        csili.dangerousnessValue,
        csili.existingControlValue,
        csili.priorityValue,
        csili.action,
        csiclg.id groupId,
        csili.customer_safety_inspection_list_id customerSafetyInspectionListId,
        csili.customer_safety_inspection_config_list_item_id customerSafetyInspectionConfigListItemId,
        csicli.description,
        IFNULL(csiliap.id, 0) actionPlanId
    FROM
        wg_customer_safety_inspection csi
    INNER JOIN wg_customer_contractor cc
        on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
    INNER JOIN wg_customer_contractor_safety_inspection ccsi
        on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
    INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
    INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
    INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
    INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
    INNER JOIN wg_customer_contractor_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csil.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
    LEFT JOIN wg_customer_safety_inspection_list_item_action_plan csiliap ON csiliap.customer_safety_inspection_list_item_id = csili.id
    WHERE cc.id = :customer_contractor_id_1 and csicl.isActive = 1 and csiclg.isActive and csicli.isActive  $andWhere";

        $results = DB::select( $query, array(
            'customer_contractor_id_1' => $customerContractorId
        ));

        return $results;
    }

    public function getHeaderFields($customerContractorId)
    {
        $query = "SELECT
        csihf.id,
        csihf.varcharValue,
        csihf.numericValue,
        csihf.dateValue,
        csichf.dataType,
        csichf.`name`,
        csi.id customerSafetyInspectionId,
        csihf.customer_safety_inspection_config_header_field_id customerSafetyInspectionConfigHeaderFieldId
    FROM
        wg_customer_safety_inspection csi
    INNER JOIN wg_customer_contractor cc
                                on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
                            INNER JOIN wg_customer_contractor_safety_inspection ccsi
                                on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
    INNER JOIN wg_customer_safety_inspection_header_field csihf ON csihf.customer_safety_inspection_id = csi.id
    INNER JOIN wg_customer_safety_inspection_config_header_field csichf ON csichf.id = csihf.customer_safety_inspection_config_header_field_id
    INNER JOIN wg_customer_safety_inspection_config_header csich ON csich.id = csichf.customer_safety_inspection_config_header_id
    WHERE cc.id = :customer_contractor_id_1 AND csihf.isActive = 1 AND csichf.isActive AND csich.isActive = 1
    ORDER BY csichf.sort";

        $results = DB::select( $query, array(
            'customer_contractor_id_1' => $customerContractorId
        ));

        return $results;
    }

    public function getValidationList($customerContractorId)
    {
        $query = "SELECT
                csiclv_d.id,
                csiclv_d.customer_safety_inspection_config_list_id customerSafetyInspectionConfigListId,
                csiclv_d.type,
                csiclv_d.description,
                csiclv_d.`value`
            FROM
                wg_customer_safety_inspection csi
            INNER JOIN wg_customer_contractor cc
             on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
            INNER JOIN wg_customer_contractor_safety_inspection ccsi
             on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
            INNER JOIN wg_customer_safety_inspection_list csil ON csil.customer_safety_inspection_id = csi.id
            INNER JOIN wg_customer_safety_inspection_config_list csicl ON csicl.id = csil.customer_safety_inspection_config_list_id
            INNER JOIN wg_customer_safety_inspection_config_list_validation csiclv_d ON csiclv_d.customer_safety_inspection_config_list_id = csicl.id
            WHERE cc.id = :customer_contractor_id_1 AND csiclv_d.isActive = 1 AND csicl.isActive = 1
            ORDER BY csiclv_d.`value`";

        $results = DB::select( $query, array(
            'customer_contractor_id_1' => $customerContractorId,
        ));

        return $results;
    }
}
