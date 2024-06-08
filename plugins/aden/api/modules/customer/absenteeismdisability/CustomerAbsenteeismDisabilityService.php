<?php

namespace AdeN\Api\Modules\Customer\AbsenteeismDisability;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CriteriaHelper;
use DB;
use Log;
use Str;
use AdeN\Api\Helpers\ExportHelper;
use Wgroup\SystemParameter\SystemParameter;

class CustomerAbsenteeismDisabilityService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getChartBar($criteria)
    {
        $data = DB::table('wg_customer_absenteeism_disability')
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })->select(
                'wg_customer_employee.customer_id',
                DB::raw("'Variación' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 1 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 2 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 3 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 4 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 5 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 6 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 7 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 8 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 9 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 10 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 11 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_disability.start) = 12 THEN wg_customer_absenteeism_disability.amountPaid ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_employee.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_disability.start', '=', $criteria->year)
            ->groupBy('wg_customer_employee.customer_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartLineDisabilityGeneralEvent($criteria)
    {
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

        if (isset($criteria->customerId)) {
            $qCalendar->where('wg_customer_employee.customer_id', $criteria->customerId);
        }

        if (!empty($criteria->workplaceId)) {
            $qCalendar->where('workplace_id', $criteria->workplaceId);
        }

        $sql = 'CREATE TEMPORARY TABLE calendar_general ' . $qCalendar->toSql();

        DB::statement($sql, $qCalendar->getBindings());


        $data = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar_general", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar_general.customer_absenteeism_disability_id');
            })
            ->select(
                'calendar_general.year AS label',
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 1 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'JAN'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 2 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'FEB'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 3 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'MAR'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 4 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'APR'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 5 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'MAY'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 6 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'JUN'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 7 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'JUL'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 8 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'AUG'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 9 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'SEP'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 10 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'OCT'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 11 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'NOV'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 12 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'DEC'")
            )
            ->where('wg_customer_employee.customer_id', $criteria->customerId)
            ->where('wg_customer_absenteeism_disability.category', 'Incapacidad') //Sin Incapacidad
            ->where('wg_customer_absenteeism_disability.cause', $criteria->cause ? $criteria->cause->value : '-1')
            ->whereIn('calendar_general.year', $criteria->yearList)
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar_general.year'
            )
            ->orderBy('calendar_general.year', 'DESC')
            ->get();

        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar_general");

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getExportData($criteria = null)
    {
        $query = DB::table('wg_customer_absenteeism_disability');

        /* Example relation*/
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_employee.job', '=', 'wg_customer_config_job.id');
        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
        })->leftjoin("wg_disability_diagnostic", function ($join) {
            $join->on('wg_customer_absenteeism_disability.diagnostic_id', '=', 'wg_disability_diagnostic.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_accident_type')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.accidentType', '=', 'absenteeism_disability_accident_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
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
        })
            ->select(
                "wg_customer_absenteeism_disability.id",
                "wg_employee.documentNumber",
                "employee_document_type.item AS documentType",
                "wg_employee.firstName",
                "wg_employee.lastName",
                "wg_customer_config_workplace.name AS workplace",
                "employee_contract_type.item AS contractType",
                "wg_customer_config_job_data.name as job",
                "wg_customer_employee.salary",
                "wg_customer_absenteeism_disability.category",
                "absenteeism_disability_type.item AS typeText",
                "absenteeism_disability_causes.item AS causeItem",
                "absenteeism_disability_accident_type.item AS accidentType",
                "wg_disability_diagnostic.code AS diagnosticCode",
                "wg_disability_diagnostic.description AS diagnosticNanme",
                "wg_customer_absenteeism_disability.start",
                "wg_customer_absenteeism_disability.end",
                "wg_customer_absenteeism_disability.numberDays",
                "wg_customer_absenteeism_disability.chargedDays",
                "wg_customer_absenteeism_disability.amountPaid",
                "wg_customer_absenteeism_disability.directCostTotal",
                "absenteeism_disability_causes.value AS causeValue",
                DB::raw("(CASE WHEN customer_absenteeism_disability_report_al.qty > 0 THEN 1 ELSE 0 END) AS hasReport"),
                DB::raw("(CASE WHEN customer_improvement_plan.qty > 0 THEN 1 ELSE 0 END) AS hasImprovementPlan"),
                DB::raw("(CASE WHEN document_inc.qty > 0 THEN 1 ELSE 0 END) AS hasInhability"),
                DB::raw("(CASE WHEN document_inv.qty > 0 THEN 1 ELSE 0 END) AS hasInvestigation"),
                DB::raw("(CASE WHEN document_rep.qty > 0 THEN 1 ELSE 0 END) AS hasReportEps"),
                DB::raw("(CASE WHEN document_rem.qty > 0 THEN 1 ELSE 0 END) AS hasReportMin"),
                DB::raw("(CASE WHEN wg_customer_absenteeism_disability.is_hour = 1 THEN 'HORA' ELSE 'DÍA' END) AS isHour"),

                "wg_customer_employee.customer_id",
                "absenteeism_disability_type.value as typeValue"
            )
            ->where('wg_customer_employee.customer_id', $criteria->customerId)
            ->when($criteria->year, function($query) use ($criteria) {
                $query->whereYear("wg_customer_absenteeism_disability.start", $criteria->year);
            })
            ->when($criteria->month, function($query) use ($criteria) {
                $query->whereMonth("wg_customer_absenteeism_disability.start", $criteria->month);
            });

        $heading = [
            "NÚMERO DE IDENTIFICACIÓN" => "documentNumber",
            "TIPO IDENTIFICACIÓN" => "documentType",
            "NOMBRES" => "firstName",
            "APELLIDOS" => "lastName",
            "TIPO CONTRATO" => "contractType",
            "CENTRO DE TRABAJO" => "workplace",
            "CARGO" => "job",
            "BASE LIQUIDACIÓN MES" => "salary",
            "TIPO AUSENTISMO" => "category",
            "TIPO INCAPACIDAD" => "typeText",
            "CAUSA AUSENTISMO" => "causeItem",
            "TIPO DE IMPACTO" => "accidentType",
            "DIAGNÓSTICO" => "diagnosticNanme",

            "FECHA INICIAL" => "start",
            "FECHA FINAL" => "end",

            "DÍA/HORA" => "isHour",

            "NÚMERO DE DÍAS/HORAS" => "numberDays",
            "DÍAS CARGADOS" => "chargedDays",
            "VALOR PAGADO POR LA ADMINISTRADORA" => "amountPaid",
            "TOTAL COSTO DIRECTO" => "directCostTotal",

            "REPORTE EPS" => "hasReportEps",
            "REPORTE ARL" => "hasReport",
            "REPORTE MINISTERIO" => "hasReportMin",
            "INCAPACIDAD" => "hasInhability",
            "INVESTIGACIÓN AT" => "hasInvestigation",
            "PLAN DE MEJORAMIENTO" => "hasImprovementPlan",
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportDisabilityGeneralEventData($criteria = null)
    {
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

        $query = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar_general", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar_general.customer_absenteeism_disability_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
            })
            ->select(
                "absenteeism_disability_causes.item AS causeItem",
                "wg_customer_employee.customer_id AS customerId",
                "wg_customer_absenteeism_disability.cause",
                'calendar_general.year AS year',
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 1 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'JAN'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 2 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'FEB'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 3 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'MAR'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 4 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'APR'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 5 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'MAY'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 6 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'JUN'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 7 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'JUL'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 8 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'AUG'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 9 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'SEP'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 10 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'OCT'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 11 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'NOV'"),
                DB::raw("SUM(CASE WHEN calendar_general.`month` = 12 THEN IF(wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad') AND calendar_general.sortorder = 1, 1, 0) END) 'DEC'")
            )
            ->whereRaw("wg_customer_absenteeism_disability.category = 'Incapacidad'")
            ->groupBy(
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'calendar_general.year'
            );

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        //Log::info($query->toSql());
        //Log::info(json_encode($criteria));

        $result = $query->orderBy('year', 'DESC')->get();

        $heading = [
            "CAUSA" => "causeItem",
            "AÑO" => "year",
            "ENERO" => "JAN",
            "FEBRERO" => "FEB",
            "MARZO" => "MAR",
            "ABRIL" => "APR",
            "MAYO" => "MAY",
            "JUNIO" => "JUN",
            "JULIO" => "JUL",
            "AGOSTO" => "AUG",
            "SEPTIEMBRE" => "SEP",
            "OCTUBRE" => "OCT",
            "NOVIEMBRE" => "NOV",
            "DICIEMBRE" => "DEC"
        ];

        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar_general");

        return ExportHelper::headings($result, $heading);
    }

    public function getExportDisabilityPersonAnalysisData($criteria = null)
    {
        $query = DB::table('wg_customer_absenteeism_disability');

        //  INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
        $query->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');

            //  INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
        })
            ->join("wg_employee", function ($join) {
                $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

                //  INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
            })
            ->join("wg_disability_diagnostic", function ($join) {
                $join->on('wg_customer_absenteeism_disability.diagnostic_id', '=', 'wg_disability_diagnostic.id');

                // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_report_al GROUP BY customer_disability_id) dr
                //ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

            })
            ->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getReportATRelation('customer_absenteeism_disability_report_al')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_report_al.customer_disability_id');

                //LEFT JOIN (SELECT COUNT(*) qty, id, customer_disability_id FROM wg_customer_absenteeism_disability_action_plan GROUP BY customer_disability_id) ap
                //ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

            })
            ->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getActionPlanRelation('customer_absenteeism_disability_action_plan')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'customer_absenteeism_disability_action_plan.customer_disability_id');

                //LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INC' GROUP BY customer_disability_id) document_inc
                //ON `document_inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

            })
            ->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inc', 'INC')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inc.customer_disability_id');

                // LEFT JOIN (SELECT COUNT(*) qty, customer_disability_id FROM wg_customer_absenteeism_disability_document WHERE type = 'INV' GROUP BY customer_disability_id) document_inv
                //ON `document_inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`

            })
            ->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getDocumentAndTypeRelation('document_inv', 'INV')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'document_inv.customer_disability_id');

                //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type') dtype
                //ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`

            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_type')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.type', '=', 'absenteeism_disability_type.value');

                //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type') ctype
                //ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`

            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
                $join->on('wg_customer_employee.contractType', '=', 'employee_contract_type.value');

                //LEFT JOIN (SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes') absenteeism_disability_causes
                //ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`

            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
                $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
            })
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_absenteeism_disability.workplace_id');
            })
            ->select(
                "wg_employee.fullName AS employee",
                "wg_customer_absenteeism_disability.start",
                "wg_customer_absenteeism_disability.end",
                "absenteeism_disability_causes.item AS origin",
                "absenteeism_disability_type.item AS type",
                "wg_customer_absenteeism_disability.numberDays",
                "wg_customer_absenteeism_disability.numberDays AS acumulateDays",
                "wg_disability_diagnostic.description",
                "wg_customer_config_workplace.name AS workplace",
                "wg_customer_absenteeism_disability.id",
                "wg_customer_absenteeism_disability.diagnostic_id",
                "wg_employee.firstName",
                "wg_employee.lastName",
                "wg_customer_absenteeism_disability.cause",
                DB::raw("DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') startDate"),
                DB::raw(" DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') endDate"),
                "wg_customer_employee.customer_id AS customerId",
                "wg_customer_absenteeism_disability.category"
            );

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        Log::info($query->toSql());

        $heading = [
            "EMPLEADO" => "employee",
            "FECHA INICIO" => "startDate",
            "FECHA FINAL" => "endDate",
            "ORIGEN" => "origin",
            "TIPO" => "type",
            "NUM DÍAS" => "numberDays",
            "NUM DÍAS ACUMULADOS" => "acumulateDays",
            "DIAGNÓSTICO" => "description",
            "CENTRO DE TRABAJO" => "workplace"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }
}
