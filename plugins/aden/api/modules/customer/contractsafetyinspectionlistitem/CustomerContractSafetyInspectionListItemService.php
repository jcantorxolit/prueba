<?php

namespace AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerContractSafetyInspectionListItemService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getChartBar($criteria)
    {
        $sql = "SELECT
                wg_customer_contract_detail.period, 
                COUNT(*) requirements,
                SUM(CASE WHEN wg_rate.`code` IS NULL THEN 1 ELSE 0 END) nocontesta,
                SUM(CASE WHEN wg_rate.`code` = 'c' THEN 1 ELSE 0 END) cumple,
                SUM(CASE WHEN wg_rate.`code` = 'cp' THEN 1 ELSE 0 END) parcial,
                SUM(CASE WHEN wg_rate.`code` = 'nc' THEN 1 ELSE 0 END) nocumple,
                SUM(CASE WHEN wg_rate.`code` = 'na' THEN 1 ELSE 0 END) noaplica
        FROM
                wg_customer_contract_detail
        INNER JOIN wg_customer_contractor ON wg_customer_contract_detail.contractor_id = wg_customer_contractor.id
        LEFT JOIN wg_rate ON wg_customer_contract_detail.rate_id = wg_rate.id		
        WHERE wg_customer_contract_detail.contractor_id = :contractor_id
        GROUP BY wg_customer_contract_detail.period";

        $data = DB::select($sql, [
            'contractor_id' => $criteria->contractorId
        ]);

        $rates = DB::table('wg_rate')->get();
       
        $config = array(
            "labelColumn" => 'period',
            "valueColumns" => [
                ['label' => 'Sin Contestar', 'field' => 'nocontesta'],
                ['label' => 'Cumple', 'field' => 'cumple', 'code' => 'c'],
                ['label' => 'Cumple Parcial', 'field' => 'parcial', 'code' => 'cp'],
                ['label' => 'No Cumple', 'field' => 'nocumple', 'code' => 'nc'],
                ['label' => 'No Aplica', 'field' => 'noaplica', 'code' => 'na'],
            ],
            "seriesLabel" => $rates
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPie($criteria)
    {
        $sql = "SELECT
                period label,
                ROUND(IFNULL((total / requirements),0), 2) AS `value`
        FROM 
        (
                SELECT
                        wg_customer_contract_detail.period, 
                        COUNT(*) requirements,
                        SUM(CASE WHEN ISNULL(wg_customer_contract_detail.rate_id) then 0 else 1 end) answers,
                        SUM(wg_rate.`value`) total
                FROM
                        wg_customer_contract_detail
                INNER JOIN wg_customer_contractor ON wg_customer_contract_detail.contractor_id = wg_customer_contractor.id
                INNER JOIN
                (
                        SELECT id, customer_id, requirement, isActive, 1 `month`, jan canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 2 `month`, feb canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 3 `month`, mar canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 4 `month`, apr canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 5 `month`, may canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 6 `month`, jun canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 7 `month`, jul canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 8 `month`, aug canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 9 `month`, sep canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 10 `month`, oct canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 11 `month`, nov canShow FROM wg_customer_periodic_requirement
                UNION ALL
                SELECT id, customer_id, requirement, isActive, 12 `month`, `dec` canShow FROM wg_customer_periodic_requirement
                ) periodic_requirement 
                    ON wg_customer_contract_detail.periodic_requirement_id = periodic_requirement.id 
                        AND wg_customer_contract_detail.`month` = periodic_requirement.`month`
                LEFT JOIN wg_rate ON wg_customer_contract_detail.rate_id = wg_rate.id		
                WHERE wg_customer_contract_detail.contractor_id = :contractor_id and periodic_requirement.isActive = 1 AND periodic_requirement.canShow = 1
                GROUP BY wg_customer_contract_detail.period
        ) periodic";

        $data = DB::select($sql, [
            'contractor_id' => $criteria->contractorId
        ]);

        return $this->chart->getChartPie($data);
    }

    public function getStats($criteria)
    {
        $sql = "SELECT                        
        COUNT(*) requirements,                        	
        ROUND((SUM(IFNULL(wg_rate.`value` ,0)) / COUNT(*)), 2) average
        FROM wg_customer_contract_detail
        INNER JOIN wg_customer_contractor ON wg_customer_contract_detail.contractor_id = wg_customer_contractor.id
        INNER JOIN
        (
                        SELECT id, customer_id, requirement, isActive, 1 `month`, jan canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 2 `month`, feb canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 3 `month`, mar canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 4 `month`, apr canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 5 `month`, may canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 6 `month`, jun canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 7 `month`, jul canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 8 `month`, aug canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 9 `month`, sep canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 10 `month`, oct canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 11 `month`, nov canShow FROM wg_customer_periodic_requirement
                        UNION ALL
                        SELECT id, customer_id, requirement, isActive, 12 `month`, `dec` canShow FROM wg_customer_periodic_requirement
        ) periodic_requirement 
                ON wg_customer_contract_detail.periodic_requirement_id = periodic_requirement.id 
                        AND wg_customer_contract_detail.`month` = periodic_requirement.`month`
        LEFT JOIN wg_rate ON wg_customer_contract_detail.rate_id = wg_rate.id		
        WHERE wg_customer_contract_detail.contractor_id = :contractor_id and periodic_requirement.isActive = 1 AND periodic_requirement.canShow = 1
        GROUP BY wg_customer_contract_detail.contractor_id";

        $data = DB::select($sql, [
            'contractor_id' => $criteria->contractorId
        ]);

        return count($data) > 0 ? $data[0] : null;
    }
}