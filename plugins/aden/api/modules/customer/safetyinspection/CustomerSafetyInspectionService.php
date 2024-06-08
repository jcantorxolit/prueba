<?php

namespace AdeN\Api\Modules\Customer\SafetyInspection;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Carbon\Carbon;


class CustomerSafetyInspectionService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getHeaderFields($customerSafetyInspectionId)
    {
        $qSub = DB::table('wg_customer_safety_inspection_header_field')
            ->where('wg_customer_safety_inspection_header_field.customer_safety_inspection_id', '=', $customerSafetyInspectionId)
            ->whereRaw('isActive = 1');

        $query = DB::table('wg_customer_safety_inspection')
            ->join('wg_customer_safety_inspection_config_header', function ($join) {
                $join->on('wg_customer_safety_inspection_config_header.id', '=', 'wg_customer_safety_inspection.customer_safety_inspection_header_id');

            })->join('wg_customer_safety_inspection_config_header_field', function ($join) {
                $join->on('wg_customer_safety_inspection_config_header_field.customer_safety_inspection_config_header_id', '=', 'wg_customer_safety_inspection_config_header.id');

            })->leftjoin(DB::raw("({$qSub->toSql()}) as customer_safety_inspection_header_field"), function ($join) {                
                $join->on('customer_safety_inspection_header_field.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');
                $join->on('customer_safety_inspection_header_field.customer_safety_inspection_config_header_field_id', '=', 'wg_customer_safety_inspection_config_header_field.id');

            })->select(
                DB::raw('IFNULL(customer_safety_inspection_header_field.id, 0) AS id'),
                'wg_customer_safety_inspection.id AS customerSafetyInspectionId',                             
                'wg_customer_safety_inspection_config_header_field.id AS customerSafetyInspectionConfigHeaderFieldId',
                'wg_customer_safety_inspection_config_header_field.name',
                'wg_customer_safety_inspection_config_header_field.dataType',
                'customer_safety_inspection_header_field.varcharValue',
                'customer_safety_inspection_header_field.numericValue',
                'customer_safety_inspection_header_field.dateValue'
            )
            ->where('wg_customer_safety_inspection.id', '=', $customerSafetyInspectionId)
            ->whereRaw('wg_customer_safety_inspection_config_header_field.isActive = 1')
            ->whereRaw('wg_customer_safety_inspection_config_header.isActive = 1')
            ->orderBy('wg_customer_safety_inspection_config_header_field.sort', 'asc')
            ->mergeBindings($qSub);

        $results = $query->get();

        foreach ($results as $field) {
            if ($field->dataType == "date" && $field->dateValue && $field->dateValue != '') {
                $field->dateValue = Carbon::parse($field->dateValue);
            } else if ($field->dataType == "int" && $field->numericValue && $field->numericValue != '') {
                $field->numericValue = floatval($field->numericValue);
            }
        }

        return $results;
    }

    public function getList($customerSafetyInspectionId)
    {
        $query = "SELECT list.id, name, customerSafetyInspectionListId, customerSafetyInspectionId, questions , answers, round((answers / questions) * 100, 2) advance, round((total / questions), 2) average, total
        FROM
                (
                                SELECT  csicl.id, csil.id as customerSafetyInspectionListId, csicl.`name`, count(*) questions, csi.id customerSafetyInspectionId
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
            'customer_safety_inspection_id_1' => $customerSafetyInspectionId,
            'customer_safety_inspection_id_2' => $customerSafetyInspectionId
        ));

        return $results;
    }

    public function getValidationList($customerSafetyInspectionId)
    {
        $query = "SELECT
            csiclv_d.id,
            csiclv_d.customer_safety_inspection_config_list_id customerSafetyInspectionConfigListId,
            csiclv_d.type,
            csiclv_d.description,
            csiclv_d.`value`
        FROM
            wg_customer_safety_inspection csi
        INNER JOIN wg_customer_safety_inspection_list csil ON csil.customer_safety_inspection_id = csi.id
        INNER JOIN wg_customer_safety_inspection_config_list csicl ON csicl.id = csil.customer_safety_inspection_config_list_id
        INNER JOIN wg_customer_safety_inspection_config_list_validation csiclv_d ON csiclv_d.customer_safety_inspection_config_list_id = csicl.id
        WHERE customer_safety_inspection_id = :customer_safety_inspection_id AND csiclv_d.isActive = 1 AND csicl.isActive = 1
        ORDER BY csiclv_d.`value`";

        $results = DB::select($query, array(
            'customer_safety_inspection_id' => $customerSafetyInspectionId,
        ));

        return $results;
    }

    public function getPrepareList($customerSafetyInspectionId)
    {

        $lists = $this->getList($customerSafetyInspectionId);
        $validationList = $this->getValidationList($customerSafetyInspectionId);

        foreach ($lists as $list) {
            $list->dangerousnessList = $this->prepareValidationList($list, $validationList, 'dangerousness');
            $list->existingControlList = $this->prepareValidationList($list, $validationList, 'existingControl');
            $list->priorityList = $this->prepareValidationList($list, $validationList, 'priority');
        }

        return $lists;
    }

    private function prepareValidationList($list, $validationList, $type)
    {
        if (!$validationList || !count($validationList)) {
            return null;
        }

        $result = [];

        foreach ($validationList as $validation) {
            if ($validation->customerSafetyInspectionConfigListId == $list->id && $validation->type == $type) {
                $result[] = $validation;
            }
        }

        return $result;
    }
}