<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismDisability;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use function GuzzleHttp\json_decode;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Helpers\ExportHelper;
use Carbon\Carbon;
use System\Models\File;

class CustomerAbsenteeismDisabilityRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerAbsenteeismDisabilityModel());

        $this->service = new CustomerAbsenteeismDisabilityService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Número Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "firstName"],
            ["alias" => "Apellidos", "name" => "lastName"],
            ["alias" => "Tipo Contrato", "name" => "contractType"],
            ["alias" => "Centro de Trabajo", "name" => "workplace"],
            ["alias" => "Tipo Ausentismo", "name" => "category"],
            ["alias" => "Tipo Incapacidad", "name" => "typeText"],
            ["alias" => "Causa incapacidad", "name" => "causeItem"],
            ["alias" => "Fecha Inicial", "name" => "start"],
            ["alias" => "Fecha Final", "name" => "end"],
        ];
    }

    public static function getFilterYears($customerId)
    {
        $query = (new CustomerAbsenteeismDisabilityRepository)->query();
        $query
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_disability.start) AS item"),
                DB::raw("YEAR(wg_customer_absenteeism_disability.start) AS value")
            )
            ->where("wg_customer_employee.customer_id", $customerId)
            ->orderBy(DB::raw("YEAR(wg_customer_absenteeism_disability.start)"), "DESC")
            ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_disability.start)"));
        return  $query->get();
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability.id",
            "documentNumber" => "wg_employee.documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "contractType" => "employee_contract_type.item AS contractType",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "category" => "wg_customer_absenteeism_disability.category",
            "typeText" => "absenteeism_disability_type.item AS typeText",
            "causeItem" => DB::raw("CASE WHEN absenteeism_disability_causes.item IS NOT NULL THEN absenteeism_disability_causes.item ELSE absenteeism_disability_causes_admin.item END AS causeItem"),

            "start" => "wg_customer_absenteeism_disability.start",
            "end" => "wg_customer_absenteeism_disability.end",
            "causeValue" => "wg_customer_absenteeism_disability.cause AS causeValue",
            "hasReport" => DB::raw("(CASE WHEN customer_absenteeism_disability_report_al.qty > 0 THEN 1 ELSE 0 END) AS hasReport"),
            "hasImprovementPlan" => DB::raw("(CASE WHEN customer_improvement_plan.qty > 0 THEN 1 ELSE 0 END) AS hasImprovementPlan"),
            "hasInhability" => DB::raw("(CASE WHEN document_inc.qty > 0 THEN 1 ELSE 0 END) AS hasInhability"),
            "hasInvestigation" => DB::raw("(CASE WHEN document_inv.qty > 0 THEN 1 ELSE 0 END) AS hasInvestigation"),
            "hasReportEps" => DB::raw("(CASE WHEN document_rep.qty > 0 THEN 1 ELSE 0 END) AS hasReportEps"),
            "hasReportMin" => DB::raw("(CASE WHEN document_rem.qty > 0 THEN 1 ELSE 0 END) AS hasReportMin"),

            "customerId" => "wg_customer_employee.customer_id",
            "typeValue" => "absenteeism_disability_type.value as typeValue",
            "year" => DB::raw("YEAR(wg_customer_absenteeism_disability.start) AS year"),
            "month" => DB::raw("MONTH(wg_customer_absenteeism_disability.start) AS month")
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

            //LEFT JOIN (SELECT `id`,`namespace`,`group`,`item`,`value` COLLATE utf8_general_ci AS `value`,`code` FROM `system_parameters` WHERE `namespace` = 'wgroup'
            //AND `group` = 'absenteeism_disability_type') absenteeism_disability_type ON `wg_customer_absenteeism_disability`.`type` = `absenteeism_disability_type`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes_admin')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes_admin.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');
        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getReportATRelation('customer_absenteeism_disability_report_al')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_report_al.customer_disability_id');
        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getImprovementRelation('customer_improvement_plan')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_improvement_plan.entityId');
        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inc', 'INC')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inc.customer_disability_id');
        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inv', 'INV')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inv.customer_disability_id');
        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_rep', 'REP')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_rep.customer_disability_id');
        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_rem', 'REM')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_rem.customer_disability_id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_absenteeism_disability.createdBy', '=', 'users.id');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_absenteeism_disability.workplace_id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allRelated($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability.id",
            "causeItem" => DB::raw("CASE WHEN absenteeism_disability_causes.item IS NOT NULL THEN absenteeism_disability_causes.item ELSE absenteeism_disability_causes_admin.item END AS causeItem"),
            "start" => "wg_customer_absenteeism_disability.start",
            "end" => "wg_customer_absenteeism_disability.end",
            "causeValue" => "wg_customer_absenteeism_disability.cause AS causeValue",
            "typeValue" => "absenteeism_disability_type.value as typeValue",
            "customerId" => "wg_customer_employee.customer_id",
            "customerEmployeeId" => "wg_customer_absenteeism_disability.customer_employee_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes_admin')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes_admin.value');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_absenteeism_disability.createdBy', '=', 'users.id');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_absenteeism_disability.workplace_id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allDisabilityGeneral($criteria)
    {
        $this->setColumns([
            "year" => 'wg_customer_absenteeism_disability.year',
            "Jan" => 'wg_customer_absenteeism_disability.jan',
            "Feb" => 'wg_customer_absenteeism_disability.feb',
            "Mar" => 'wg_customer_absenteeism_disability.mar',
            "Apr" => 'wg_customer_absenteeism_disability.apr',
            "May" => 'wg_customer_absenteeism_disability.may',
            "Jun" => 'wg_customer_absenteeism_disability.jun',
            "Jul" => 'wg_customer_absenteeism_disability.jul',
            "Aug" => 'wg_customer_absenteeism_disability.aug',
            "Sep" => 'wg_customer_absenteeism_disability.sep',
            "Oct" => 'wg_customer_absenteeism_disability.oct',
            "Nov" => 'wg_customer_absenteeism_disability.nov',
            "Dec" => 'wg_customer_absenteeism_disability.dec',
            "cause" => "wg_customer_absenteeism_disability.cause",
            "customerId" => "wg_customer_absenteeism_disability.customerId",
            "workplaceId" => "wg_customer_absenteeism_disability.workplace_id as workplaceId"
        ]);

        $this->parseCriteria($criteria);

        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar_general");

        $qCalendar = DB::table(DB::raw("(SELECT @row_num := 1) x, (SELECT @prev_value := '') y, wg_customer_absenteeism_disability"))
            ->join("wg_calendar", function ($join) {
                $join->on('wg_calendar.full_date', '>=', DB::raw('DATE(wg_customer_absenteeism_disability.start)'));
                $join->on('wg_calendar.full_date', '<=', DB::raw('DATE(wg_customer_absenteeism_disability.end)'));
            })
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_absenteeism_disability.customer_employee_id');
            })
            ->select(
                'wg_customer_absenteeism_disability.id AS customer_absenteeism_disability_id',
                DB::raw("EXTRACT(YEAR_MONTH FROM wg_calendar.full_date) AS period"),
                DB::raw("YEAR(wg_calendar.full_date) AS year"),
                DB::raw("MONTH(wg_calendar.full_date) AS month"),
                DB::raw("COUNT(*) AS days"),
                DB::raw("@row_num := CASE WHEN @prev_value = wg_customer_absenteeism_disability.id THEN @row_num + 1 ELSE 1 END AS sortorder"),
                DB::raw("@prev_value := wg_customer_absenteeism_disability.id AS current_group")
            )
            ->groupBy(
                DB::raw("EXTRACT(YEAR_MONTH FROM wg_calendar.full_date)"),
                'wg_customer_absenteeism_disability.id'
            )
            ->orderBy('wg_customer_absenteeism_disability.id');

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        if ($customerId != null) {
            $qCalendar->where('wg_customer_employee.customer_id', $customerId->value);
        }

        $sql = 'CREATE TEMPORARY TABLE calendar_general ' . $qCalendar->toSql();

        DB::statement($sql, $qCalendar->getBindings());

        $q1 = DB::table('wg_customer_absenteeism_disability');

        $q1->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("calendar_general", function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar_general.customer_absenteeism_disability_id');
        })
            ->select(
                'calendar_general.year',
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 1 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'jan'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 2 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'feb'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 3 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'mar'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 4 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'apr'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 5 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'may'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 6 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'jun'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 7 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'jul'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 8 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'aug'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 9 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'sep'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 10 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'oct'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 11 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'nov'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 12 THEN IF(wg_customer_absenteeism_disability.type = 'Inicial' AND calendar_general.sortorder = 1, 1, 0) END) AS 'dec'"),
                "wg_customer_absenteeism_disability.cause",
                "wg_customer_employee.customer_id AS customerId",
                "wg_customer_absenteeism_disability.workplace_id"
            )
            ->where('wg_customer_absenteeism_disability.category', 'Incapacidad')
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar_general.year'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerId") {
                        $q1->where(SqlHelper::getPreparedField("wg_customer_employee.customer_id"), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }

                    if ($item->field == "cause") {
                        $q1->where(SqlHelper::getPreparedField("wg_customer_absenteeism_disability.cause"), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }

                    if ($item->field == "workplaceId") {
                        $q1->where(SqlHelper::getPreparedField("wg_customer_absenteeism_disability.workplace_id"), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_absenteeism_disability")))
            ->mergeBindings($q1);

        $this->applyCriteria($query, $criteria, ['customerId', 'cause']);

        $data = $this->get($query, $criteria);

        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar_general");

        return $data;
    }

    public function findOne($id)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability.id",
            "documentNumber" => "wg_employee.documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "fullName" => "wg_employee.fullName",
            "job" => "wg_customer_config_job_data.name AS job",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "contractType" => "employee_contract_type.item AS contractType",
            "category" => "wg_customer_absenteeism_disability.category",
            "typeText" => "absenteeism_disability_type.item AS typeText",
            "causeItem" => "absenteeism_disability_causes.item AS causeItem",

            "start" => "wg_customer_absenteeism_disability.start",
            "end" => "wg_customer_absenteeism_disability.end",
            "causeValue" => "absenteeism_disability_causes.value AS causeValue",
            "diagnostic" => "wg_disability_diagnostic.description AS diagnostic",

            "customerId" => "wg_customer_employee.customer_id",
        ]);

        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');
        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_employee.workPlace');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');
        })->leftjoin("wg_disability_diagnostic", function ($join) {
            $join->on('wg_disability_diagnostic.id', '=', 'wg_customer_absenteeism_disability.diagnostic_id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_absenteeism_disability.createdBy', '=', 'users.id');
        })->where('wg_customer_absenteeism_disability.id', $id);

        return $query->select($this->columns)->first();
    }

    public function allDiagnosticAnalysis($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "diagnosticId" => "wg_customer_absenteeism_disability.diagnostic_id",
            "description" => "wg_disability_diagnostic.description",
            "start" => "wg_customer_absenteeism_disability.start",
            "end" => "wg_customer_absenteeism_disability.end",
            "records" => DB::raw("COUNT(*) AS records"),
            "days" => DB::raw("SUM(wg_customer_absenteeism_disability.numberDays) AS days"),
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "customerId" => "wg_customer_employee.customer_id",
            "category" => "wg_customer_absenteeism_disability.category",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        // INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');

            // INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

            // INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
        })->join("wg_disability_diagnostic", function ($join) {
            $join->on('wg_customer_absenteeism_disability.diagnostic_id', '=', 'wg_disability_diagnostic.id');

            //LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_report_al GROUP BY customer_disability_id) customer_absenteeism_disability_report_al
            //ON `customer_absenteeism_disability_report_al`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getReportATRelation('customer_absenteeism_disability_report_al')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_report_al.customer_disability_id');

            //LEFT JOIN (SELECT COUNT(*) qty, id, customer_disability_id FROM wg_customer_absenteeism_disability_action_plan GROUP BY customer_disability_id) customer_absenteeism_disability_action_plan
            //ON `customer_absenteeism_disability_action_plan`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getActionPlanRelation('customer_absenteeism_disability_action_plan')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_action_plan.customer_disability_id');

            //LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INC' GROUP BY customer_disability_id) document_inc
            //ON `document_inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inc', 'INC')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inc.customer_disability_id');

            // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INV' GROUP BY customer_disability_id) document_inv
            //ON `document_inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inv', 'INV')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inv.customer_disability_id');

            //LEFT JOIN ( SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type') dtype
            //ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type') ctype
            //ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes') absenteeism_disability_causes
            //ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_absenteeism_disability.workplace_id');
        })
            ->groupBy('wg_disability_diagnostic.description');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allDiagnosticSummary($criteria)
    {
        $this->setColumns([
            "cause" => 'wg_customer_absenteeism_disability.cause',
            "quantity" => 'wg_customer_absenteeism_disability.quantity',
            "period" => 'wg_customer_absenteeism_disability.period',
            "type" => "wg_customer_absenteeism_disability.type",
            "customerId" => "wg_customer_absenteeism_disability.customerId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_disability');

        $q1->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
        })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes', 'p')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.cause', '=', 'p.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes_admin', 'pa')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.cause', '=', 'pa.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type', 'dt')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.type', '=', 'dt.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type', 'ct')), function ($join) {
                $join->on('wg_customer_employee.contractType', '=', 'ct.value');
            })
            ->select(
                DB::raw("IFNULL(CASE WHEN wg_customer_absenteeism_disability.category = 'Administrativo' THEN pa.item ELSE p.item END, 'SIN CAUSA') AS cause"),
                //DB::raw("COUNT(*) AS quantity"),
                DB::raw("SUM(
                    CASE WHEN wg_customer_absenteeism_disability.type = 'Inicial' THEN 1
                        WHEN wg_customer_absenteeism_disability.category = 'Administrativo' THEN 1
                        WHEN wg_customer_absenteeism_disability.type = 'Sin Incapacidad'
                        AND (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                        AND wg_customer_absenteeism_disability.accidentType = 'L' THEN 1
                        WHEN wg_customer_absenteeism_disability.type = 'Sin Incapacidad'
                            AND (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                            AND wg_customer_absenteeism_disability.accidentType = 'M'
                            AND (wg_customer_absenteeism_disability.customer_absenteeism_disability_parent_id IS NULL OR wg_customer_absenteeism_disability.customer_absenteeism_disability_parent_id = 0) THEN 1
                    ELSE 0 END
                ) AS quantity"),
                DB::raw("DATE_FORMAT(`start`, '%Y%m') AS period"),
                "ct.item AS type",
                "wg_customer_employee.customer_id AS customerId"
            )
            ->groupBy('p.item', DB::raw("DATE_FORMAT(`start`, '%Y%m')"), 'ct.item');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_employee.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                    if ($item->field == 'period') {
                        $q1->where(SqlHelper::getPreparedField(DB::raw("DATE_FORMAT(`start`, '%Y%m') AS period")), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                    if ($item->field == 'cause') {
                        $q1->where(SqlHelper::getPreparedField(DB::raw("IFNULL(CASE WHEN wg_customer_absenteeism_disability.category = 'Administrativo' THEN pa.item ELSE p.item END, 'SIN CAUSA') AS cause")), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_absenteeism_disability")))
            ->mergeBindings($q1);

        $this->applyCriteria($query, $criteria, ['customerId', 'cause', 'period']);

        return $this->get($query, $criteria);
    }

    public function allPersonAnalysis($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "employee" => "wg_employee.fullName AS employee ",
            "start" => "wg_customer_absenteeism_disability.start",
            "end" => "wg_customer_absenteeism_disability.end",
            "origin" => "absenteeism_disability_causes.item AS origin",
            "type" => "absenteeism_disability_type.item AS type",
            "numberDays" => "wg_customer_absenteeism_disability.numberDays",
            "acumulateDays" => "wg_customer_absenteeism_disability.numberDays AS acumulateDays",
            "description" => "wg_disability_diagnostic.description",
            "id" => "wg_customer_absenteeism_disability.id",
            "diagnosticId" => "wg_customer_absenteeism_disability.diagnostic_id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "cause" => "wg_customer_absenteeism_disability.cause",
            "startDate" => DB::raw("DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') startDate"),
            "endDate" => DB::raw(" DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') endDate"),
            "customerId" => "wg_customer_employee.customer_id",
            "category" => "wg_customer_absenteeism_disability.category",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        //  INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');

            //  INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

            //  INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
        })->join("wg_disability_diagnostic", function ($join) {
            $join->on('wg_customer_absenteeism_disability.diagnostic_id', '=', 'wg_disability_diagnostic.id');

            // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_report_al GROUP BY customer_disability_id) dr
            //ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getReportATRelation('customer_absenteeism_disability_report_al')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_report_al.customer_disability_id');

            //LEFT JOIN (SELECT COUNT(*) qty, id, customer_disability_id FROM wg_customer_absenteeism_disability_action_plan GROUP BY customer_disability_id) ap
            //ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getActionPlanRelation('customer_absenteeism_disability_action_plan')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_action_plan.customer_disability_id');

            //LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INC' GROUP BY customer_disability_id) document_inc
            //ON `document_inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inc', 'INC')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inc.customer_disability_id');

            // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INV' GROUP BY customer_disability_id) document_inv
            //ON `document_inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inv', 'INV')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inv.customer_disability_id');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type') dtype
            //ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type') ctype
            //ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes') absenteeism_disability_causes
            //ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_absenteeism_disability.workplace_id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allDaysAnalysis($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability.id",
            "description" => "wg_disability_diagnostic.description AS disability",
            "item" => "absenteeism_disability_causes.item AS causeItem",
            "startDate" => "wg_customer_absenteeism_disability.start AS startDate",
            "endDate" => "wg_customer_absenteeism_disability.end AS endDate",
            "records" => DB::raw("COUNT(*) AS records"),
            "days" => DB::raw("SUM(wg_customer_absenteeism_disability.numberDays) AS days"),
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "customerId" => "wg_customer_employee.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        //  INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');

            //  INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

            //  INNER JOIN wg_disability_diagnostic dd ON wg_customer_absenteeism_disability.diagnostic_id = dd.id
        })->join("wg_disability_diagnostic", function ($join) {
            $join->on('wg_customer_absenteeism_disability.diagnostic_id', '=', 'wg_disability_diagnostic.id');

            // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_report_al GROUP BY customer_disability_id) dr
            //ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getReportATRelation('customer_absenteeism_disability_report_al')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_report_al.customer_disability_id');

            //LEFT JOIN (SELECT COUNT(*) qty, id, customer_disability_id FROM wg_customer_absenteeism_disability_action_plan GROUP BY customer_disability_id) ap
            //ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getActionPlanRelation('customer_absenteeism_disability_action_plan')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_action_plan.customer_disability_id');

            //LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INC' GROUP BY customer_disability_id) document_inc
            //ON `document_inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inc', 'INC')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inc.customer_disability_id');

            // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INV' GROUP BY customer_disability_id) document_inv
            //ON `document_inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

        })->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inv', 'INV')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inv.customer_disability_id');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type') dtype
            //ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type') ctype
            //ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');

            //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes') absenteeism_disability_causes
            //ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_absenteeism_disability.workplace_id');
        })
            ->groupBy('absenteeism_disability_causes.item');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            $model->format = sprintf(
                'Nro Identificación: %s| Nombre: %s| Fecha Incial: %s| Fecha Final: %s| Centro de Trabajo: %s| Cargo: %s| Diagnóstico: %s',
                $model->documentNumber,
                $model->fullName,
                $model->startDate,
                $model->endDate,
                $model->workplace,
                $model->job,
                $model->diagnostic
            );
            return $model;
        }
    }

    public function parseModelWithFormatRelations($model)
    {
        $entity = json_decode(json_encode($model));

        if ($entity) {
            $entity->format = sprintf(
                'Nro Identificación: %s| Nombre: %s| Fecha Incial: %s| Fecha Final: %s| Centro de Trabajo: %s| Cargo: %s| Diagnóstico: %s',
                $entity->documentNumber,
                $entity->fullName,
                $entity->start,
                $entity->end,
                $entity->workplace,
                $entity->job,
                $entity->diagnostic
            );
        }

        return $entity;
    }

    public function getChartBar($criteria)
    {
        return $this->service->getChartBar($criteria);
    }

    public function getChartLineDisabilityGeneralEvent($criteria)
    {
        return $this->service->getChartLineDisabilityGeneralEvent($criteria);
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'HISTORICO_AUSENTISMO_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'AUSENTISMOS', $data);
    }

    public function exportExcelDisabilityGeneral($criteria)
    {
        $data = $this->service->getExportDisabilityGeneralEventData($criteria);
        $filename = 'ANALISIS_DE_EVENTOS_AUSENTISMO_GENERAL_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Matriz', $data);
    }

    public function exportExcelDisabilityPersonAnalysis($criteria)
    {
        $data = $this->service->getExportDisabilityPersonAnalysisData($criteria);
        $filename = 'ANALISIS_PERSONA_REPORTE_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Matriz', $data);
    }

    public function getTemplateFile()
    {
        $instance = CmsHelper::getInstance();
        $filePath = "templates/$instance/plantilla_importacion_ausentismos.xlsx";
        return response()->download(CmsHelper::getStorageTemplateDir($filePath));
    }


    public function getWorkplaceList($customerId, $cause, $years)
    {
        $years = implode(',', $years);

        return DB::table('wg_customers')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->join('wg_customer_absenteeism_disability as ad', function ($join) use ($cause) {
                $join->on('ad.workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->where('ad.cause', $cause);
            })
            ->join('wg_calendar', function ($join) {
                $join->whereRaw("wg_calendar.full_date >= DATE(ad.start) and wg_calendar.full_date <= DATE(ad.end)");
            })
            ->where('wg_customer_config_workplace.customer_id', $customerId)
            ->where('wg_customer_config_workplace.status', '=', 'Activo')
            ->whereRaw("year(wg_calendar.full_date) in ($years)")
            ->orderBy('wg_customer_config_workplace.name')
            ->select('wg_customer_config_workplace.*')
            ->distinct()
            ->get()
            ->toArray();
    }


    public function getCustomerWorkplaceList($customerId, $period)
    {
        // return DB::table('wg_customers')
        //     ->join('wg_customer_config_workplace', function ($join) {
        //         $join->on('wg_customers.id', '=', 'wg_customer_config_workplace.customer_id');
        //     })
        //     ->join('wg_customer_absenteeism_disability as ad', function ($join) {
        //         $join->on('ad.workplace_id', '=', 'wg_customer_config_workplace.id');
        //     })
        //     ->join('wg_calendar', function ($join) {
        //         $join->whereRaw("wg_calendar.full_date >= DATE(ad.start) and wg_calendar.full_date <= DATE(ad.end)");
        //     })
        //     ->where('wg_customer_config_workplace.customer_id', $customerId)
        //     ->where('wg_customer_config_workplace.status', '=', 'Activo')
        //     ->orderBy('wg_customer_config_workplace.name')
        //     ->select('wg_customer_config_workplace.*')
        //     ->distinct()
        //     ->get()
        //     ->toArray();

        $q1 = DB::table('wg_customer_absenteeism_indicator as abs')
            ->join('wg_customer_config_workplace', 'wg_customer_config_workplace.id', '=', 'abs.workCenter')
            ->where('abs.resolution', '0312')
            ->whereIn('abs.classification', ['AL', 'ELC', 'EG', 'AL', 'AT'])
            ->where('abs.customer_id', $customerId)
            ->whereYear('abs.periodDate', $period)
            ->whereNotNull('wg_customer_config_workplace.id')
            ->groupBy('abs.workCenter')
            ->select('wg_customer_config_workplace.*');

        $q2 = DB::table('wg_customer_occupational_investigation_al as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->leftJoin('wg_customer_config_workplace as workplace', 'workplace.id', '=', 'ce.workPlace')
            ->where('ce.customer_id', $customerId)
            ->where('o.accidentDate', $period)
            ->whereNotNull('workplace.id')
            ->groupBy('ce.workPlace')
            ->select('workplace.*');

        $q1->union($q2)->mergeBindings($q2);

        return DB::table(DB::raw("({$q1->toSql()}) as work_place"))
            ->mergeBindings($q1)
            ->distinct()
            ->get()
            ->toArray();
    }

    public function getCustomeAbsenteeismDisabilityPeriodList($customerId)
    {
        return DB::table('wg_customers')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->join('wg_customer_absenteeism_disability as ad', function ($join) {
                $join->on('ad.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join('wg_calendar', function ($join) {
                $join->whereRaw("wg_calendar.full_date >= DATE(ad.start) and wg_calendar.full_date <= DATE(ad.end)");
            })
            ->where('wg_customer_config_workplace.customer_id', $customerId)
            ->where('wg_customer_config_workplace.status', '=', 'Activo')
            ->groupBy(DB::raw('YEAR(ad.start)'))
            ->select(
                DB::raw('YEAR(ad.start) AS item'),
                DB::raw('YEAR(ad.start) AS value')
            )
            ->orderBy(DB::raw('YEAR(ad.start)'), "DESC")
            ->distinct()
            ->get()
            ->toArray();
    }

    public function getCustomerWorkplacePeriodList($customerId)
    {
        // return DB::table('wg_customers')
        //     ->join('wg_customer_config_workplace', function ($join) {
        //         $join->on('wg_customers.id', '=', 'wg_customer_config_workplace.customer_id');
        //     })
        //     ->join('wg_customer_absenteeism_disability as ad', function ($join) {
        //         $join->on('ad.workplace_id', '=', 'wg_customer_config_workplace.id');
        //     })
        //     ->join('wg_calendar', function ($join) {
        //         $join->whereRaw("wg_calendar.full_date >= DATE(ad.start) and wg_calendar.full_date <= DATE(ad.end)");
        //     })
        //     ->where('wg_customer_config_workplace.customer_id', $customerId)
        //     ->where('wg_customer_config_workplace.status', '=', 'Activo')
        //     ->orderBy('wg_customer_config_workplace.name')
        //     ->select('wg_customer_config_workplace.*')
        //     ->distinct()
        //     ->get()
        //     ->toArray();

        $q1 = DB::table('wg_customer_absenteeism_indicator as abs')
            ->join('wg_customer_config_workplace', 'wg_customer_config_workplace.id', '=', 'abs.workCenter')
            ->where('abs.resolution', '0312')
            ->whereIn('abs.classification', ['AL', 'ELC', 'EG', 'AT'])
            ->where('abs.customer_id', $customerId)
            ->groupBy(DB::raw('YEAR(abs.periodDate)'))
            ->select(
                DB::raw('YEAR(abs.periodDate) AS item'),
                DB::raw('YEAR(abs.periodDate) AS value')
            );

        $q2 = DB::table('wg_customer_occupational_investigation_al as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->leftJoin('wg_customer_config_workplace as workplace', 'workplace.id', '=', 'ce.workPlace')
            ->where('ce.customer_id', $customerId)
            ->groupBy(DB::raw('YEAR(o.accidentDate)'))
            ->select(
                DB::raw('YEAR(o.accidentDate) AS item'),
                DB::raw('YEAR(o.accidentDate) AS value')
            );

        $q1->union($q2)->mergeBindings($q2);

        return DB::table(DB::raw("({$q1->toSql()}) as work_place"))
            ->mergeBindings($q1)
            ->distinct()
            ->orderBy('item', 'DESC')
            ->get()
            ->toArray();
    }
}
