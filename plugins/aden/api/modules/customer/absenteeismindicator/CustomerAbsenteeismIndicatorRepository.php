<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismIndicator;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Queue;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\AbsenteeismIndicator\CustomerAbsenteeismIndicatorJob;

class CustomerAbsenteeismIndicatorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerAbsenteeismIndicatorModel());

        $this->service = new CustomerAbsenteeismIndicatorService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Número Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "firstName"],
            ["alias" => "Apellidos", "name" => "lastName"],
            ["alias" => "Tipo Ausentismo", "name" => "category"],
            ["alias" => "Tipo Incapacidad", "name" => "typeText"],
            ["alias" => "Causa incapacidad", "name" => "causeItem"],
            ["alias" => "Fecha Inicial", "name" => "start"],
            ["alias" => "Fecha Final", "name" => "end"],
        ];
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
            "id" => "wg_customer_absenteeism_indicator.id",
            "classification" => "ctype.item AS classification",
            "period" => "wg_customer_absenteeism_indicator.period",
            "name" => "wg_customer_config_workplace.name",
            "manHoursWorked" => DB::raw("IFNULL(wg_customer_absenteeism_indicator.manHoursWorked, 0) AS manHoursWorked"),
            "disabilityDays" => "wg_customer_absenteeism_indicator.disabilityDays",
            "eventNumber" => "wg_customer_absenteeism_indicator.eventNumber",
            "directCost" => "wg_customer_absenteeism_indicator.directCost",
            "indirectCost" => "wg_customer_absenteeism_indicator.indirectCost",
            "diseaseRate" => DB::raw("IFNULL(wg_customer_absenteeism_indicator.diseaseRate, 0) AS diseaseRate"),
            "frequencyIndex" => DB::raw("IFNULL(wg_customer_absenteeism_indicator.frequencyIndex, 0) AS frequencyIndex"),
            "severityIndex" => DB::raw("IFNULL(wg_customer_absenteeism_indicator.severityIndex, 0) AS severityIndex"),
            "disablingInjuriesIndex" => DB::raw("IFNULL(wg_customer_absenteeism_indicator.disablingInjuriesIndex, 0) AS disablingInjuriesIndex"),
            "resolution" => "wg_customer_absenteeism_indicator.resolution",
            "customerId" => "wg_customer_absenteeism_indicator.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_absenteeism_indicator.workCenter', '=', 'wg_customer_config_workplace.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_indicator_period', 'dtype')), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.period', '=', 'dtype.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_classification', 'ctype')), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.classification', '=', 'ctype.value');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummary($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "label" => "indicator.label",
            "value" => "indicator.value",
            "goal" => "indicator.goal",
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("'Tasa de Accidentalidad' AS label"),
                'wg_customer_absenteeism_indicator.diseaseRate AS value',
                DB::raw("0 AS goal")
            );

        $q2 = $this->getQueryForUnion('Eventos', 'eventNumber', 'targetEvent');
        $q3 = $this->getQueryForUnion('Dias Incapacitantes', 'disabilityDays', 'targetDay');
        $q4 = $this->getQueryForUnion('Indice de Frecuencia (IF)', 'frequencyIndex', 'targetIF');
        $q5 = $this->getQueryForUnion('Indice de Severidad (IS)', 'severityIndex', 'targetIS');
        $q6 = $this->getQueryForUnion('Indice de Lesiones Incapacitantes (ILI)', 'disablingInjuriesIndex', 'targetILI');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'id') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        $q2->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        $q3->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        $q4->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        $q5->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        $q6->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $q1->union($q2)->union($q3)->union($q4)->union($q5)->union($q6);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as indicator")))
            ->mergeBindings($q1);

        $this->applyCriteria($query, $criteria, ['id']);

        return $this->get($query, $criteria);
    }

    public function allParentResolution0312($criteria)
    {
        $this->setColumns([
            "classification" => "wg_customer_absenteeism_indicator.classification",
            "period" => "wg_customer_absenteeism_indicator.period",
            "disabilityDays" => "wg_customer_absenteeism_indicator.disabilityDays",
            "eventNumber" => "wg_customer_absenteeism_indicator.eventNumber",
            "chargedDays" => "wg_customer_absenteeism_indicator.chargedDays",
            "eventMortalNumber" => "wg_customer_absenteeism_indicator.eventMortalNumber",
            "programedDays" => "wg_customer_absenteeism_indicator.programedDays",
            "employeeQuantity" => "wg_customer_absenteeism_indicator.employeeQuantity",
            "cause" => "wg_customer_absenteeism_indicator.cause",
            "customerId" => "wg_customer_absenteeism_indicator.customer_id AS customerId",
            "resolution" => "wg_customer_absenteeism_indicator.resolution",
            "periodCode" => "wg_customer_absenteeism_indicator.periodCode",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('period', 'DESC');
        }

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_absenteeism_indicator.workCenter', '=', 'wg_customer_config_workplace.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_indicator_period')), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.period', '=', 'absenteeism_indicator_period.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.classification', '=', 'absenteeism_disability_causes.value');
            })
            ->select(
                "absenteeism_disability_causes.item AS classification",
                "absenteeism_indicator_period.item AS period",
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.disabilityDays, 0)) AS disabilityDays"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.eventNumber, 0)) AS eventNumber"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.chargedDays, 0)) AS chargedDays"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.eventMortalNumber, 0)) AS eventMortalNumber"),
                DB::raw("IFNULL(wg_customer_absenteeism_indicator.programedDays, 0) AS programedDays"),
                //DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.programedDays, 0)) AS programedDays"),
                "wg_customer_absenteeism_indicator.employeeQuantity",
                "wg_customer_absenteeism_indicator.classification AS cause",
                "wg_customer_absenteeism_indicator.customer_id",
                "wg_customer_absenteeism_indicator.resolution",
                "wg_customer_absenteeism_indicator.period AS periodCode"
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.classification',
                'wg_customer_absenteeism_indicator.period',
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.resolution'
            );

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_absenteeism_indicator")));

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allDetailResolution0312($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_absenteeism_indicator.id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "disabilityDays" => "wg_customer_absenteeism_indicator.disabilityDays",
            "eventNumber" => "wg_customer_absenteeism_indicator.eventNumber",
            "chargedDays" => "wg_customer_absenteeism_indicator.chargedDays",
            "eventMortalNumber" => "wg_customer_absenteeism_indicator.eventMortalNumber",
            "period" => "absenteeism_indicator_period.item AS period",
            "periodCode" => "wg_customer_absenteeism_indicator.period AS periodCode",
            "cause" => "wg_customer_absenteeism_indicator.classification AS cause",
            "classification" => "absenteeism_disability_causes.item AS classification",
            "customerId" => "wg_customer_absenteeism_indicator.customer_id AS customerId",
            "resolution" => "wg_customer_absenteeism_indicator.resolution",
        ]);

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('workplace', 'ASC');
        }

        $query = $this->query();

        $query->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_absenteeism_indicator.workCenter', '=', 'wg_customer_config_workplace.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_indicator_period')), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.period', '=', 'absenteeism_indicator_period.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.classification', '=', 'absenteeism_disability_causes.value');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allFrequencyAccidentality($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "month" => 'wg_month.item AS month',
            "eventNumber" => 'wg_customer_absenteeism_indicator.eventNumber',
            "employeeQuantity" => 'wg_customer_absenteeism_indicator.employeeQuantity',
            "result" => 'wg_customer_absenteeism_indicator.result',
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`eventNumber`) AS eventNumber"),
                'wg_customer_absenteeism_indicator.employeeQuantity',
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) / wg_customer_absenteeism_indicator.employeeQuantity * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $q1->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw(SystemParameter::getRelationTable('month', 'wg_month'))))
            ->mergeBindings($q1);

        $query->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.monthValue', '=', 'wg_month.value');
        })->orderBy(DB::raw("CONVERT(wg_month.value, SIGNED)"));

        $this->applyCriteria($query, $criteria, ['customerId', 'year']);

        return $this->get($query, $criteria);
    }

    public function allSeverityAccidentality($criteria)
    {
        $this->setColumns([
            "month" => 'wg_month.item AS month',
            "disabilityDays" => 'wg_customer_absenteeism_indicator.disabilityDays',
            "chargedDays" => 'wg_customer_absenteeism_indicator.chargedDays',
            "employeeQuantity" => 'wg_customer_absenteeism_indicator.employeeQuantity',
            "result" => 'wg_customer_absenteeism_indicator.result',
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) AS disabilityDays"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.chargedDays) AS chargedDays"),
                'wg_customer_absenteeism_indicator.employeeQuantity',
                DB::raw("(SUM(wg_customer_absenteeism_indicator.disabilityDays) + SUM(wg_customer_absenteeism_indicator.chargedDays)) / wg_customer_absenteeism_indicator.employeeQuantity * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $q1->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw(SystemParameter::getRelationTable('month', 'wg_month'))))
            ->mergeBindings($q1);

        $query->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.monthValue', '=', 'wg_month.value');
        })->orderBy(DB::raw("CONVERT(wg_month.value, SIGNED)"));

        $this->applyCriteria($query, $criteria, ['customerId', 'year']);

        return $this->get($query, $criteria);
    }

    public function allMortalProportionAccidentality($criteria)
    {
        $this->setColumns([
            "year" => DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
            "eventMortalNumber" => DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) AS eventMortalNumber"),
            "eventNumber" => DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
            "result" => DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) / SUM(wg_customer_absenteeism_indicator.eventNumber) * 100 AS result"),
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            )
            ->orderBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"), "DESC");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $query->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $this->applyCriteria($query, $criteria, ['customerId', 'year']);

        return $this->get($query, $criteria);
    }

    public function allAbsenteeismMedicalCause($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "month" => 'wg_month.item AS month',
            "disabilityDays" => 'wg_customer_absenteeism_indicator.disabilityDays',
            "programedDays" => 'wg_customer_absenteeism_indicator.programedDays',
            "result" => 'wg_customer_absenteeism_indicator.result',
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) AS disabilityDays"),
                DB::raw("(wg_customer_absenteeism_indicator.programedDays * wg_customer_absenteeism_indicator.employeeQuantity)  AS programedDays"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) / wg_customer_absenteeism_indicator.programedDays * 100 AS result")
            )
            //->whereIn('wg_customer_absenteeism_indicator.classification', ['EG', 'AL', 'ELC'])
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['EG', 'AT', 'AL', 'ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $q1->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw(SystemParameter::getRelationTable('month', 'wg_month'))))
            ->mergeBindings($q1);

        $query->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"), function ($join) {
            $join->on('wg_customer_absenteeism_indicator.monthValue', '=', 'wg_month.value');
        })->orderBy(DB::raw("CONVERT(wg_month.value, SIGNED)"));

        $this->applyCriteria($query, $criteria, ['customerId', 'year']);

        return $this->get($query, $criteria);
    }

    public function allOccupationalDiseaseFatalityRate($criteria)
    {
        $this->setColumns([
            "year" => DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
            "eventMortalNumber" => DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) AS eventMortalNumber"),
            "eventNumber" => DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
            "result" => DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) / SUM(wg_customer_absenteeism_indicator.eventNumber) * 100 AS result"),
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            )
            ->orderBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"), "DESC");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $query->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }
        $this->applyCriteria($query, $criteria, ['customerId', 'year']);
        return $this->get($query, $criteria);
    }

    public function allOccupationalDiseasePrevalence($criteria)
    {
        $this->setColumns([
            "year" => 'wg_customer_absenteeism_indicator.year',
            "diagnosticAll" => 'wg_customer_absenteeism_indicator.diagnosticAll',
            "employeeQuantity" => DB::raw("wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty AS employeeQuantity"),
            "result" => DB::raw("wg_customer_absenteeism_indicator.diagnosticAll / (wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty) * 100000 AS result"),
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                'wg_customer_absenteeism_indicator.customer_id AS customer',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS month"),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticAll + IFNULL((SELECT SUM(diagnosticAll)
                            FROM (SELECT IFNULL(MAX(wg_customer_absenteeism_indicator.diagnosticAll), 0) AS diagnosticAll,
                                            customer_id,
                                            classification,
                                            resolution,
                                            YEAR(wg_customer_absenteeism_indicator.periodDate) AS year_value
                                        FROM  wg_customer_absenteeism_indicator
                                        GROUP BY customer_id, classification, resolution, year_value
                        ) wg_customer_absenteeism_indicator
                            WHERE `wg_customer_absenteeism_indicator`.`customer_id` = customer
                                AND `wg_customer_absenteeism_indicator`.`classification` IN ('ELC')
                                AND `wg_customer_absenteeism_indicator`.`resolution` = '0312'
                                AND wg_customer_absenteeism_indicator.year_value < year
                    ), 0) diagnosticAll"),
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`employeeQuantity`) AS employeeQuantity"),
                DB::raw("COUNT(*) qty")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            /*->whereIn('wg_customer_absenteeism_indicator.id', function ($query) use ($criteria) {
                $query->select(DB::raw("MAX(id)"))
                    ->from('wg_customer_absenteeism_indicator')
                    ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
                    ->where('wg_customer_absenteeism_indicator.resolution', '0312')
                    ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"));
                if ($criteria != null) {
                    if ($criteria->mandatoryFilters != null) {
                        foreach ($criteria->mandatoryFilters as $item) {
                            if ($item->field == 'customerId') {
                                $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            }
                        }
                    }
                }
            })*/
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }


        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator")))
            ->mergeBindings($q1);

        $query
            ->groupBy('wg_customer_absenteeism_indicator.year')
            ->orderBy('wg_customer_absenteeism_indicator.year', "DESC");

        $this->applyCriteria($query, $criteria, ['customerId', 'year']);

        return $this->get($query, $criteria);
    }

    public function allOccupationalDiseaseIncidence($criteria)
    {
        $this->setColumns([
            "year" => 'wg_customer_absenteeism_indicator.year',
            "diagnosticNew" => 'wg_customer_absenteeism_indicator.diagnosticNew',
            "employeeQuantity" => DB::raw("wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty AS employeeQuantity"),
            "result" => DB::raw("wg_customer_absenteeism_indicator.diagnosticNew / (wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty) * 100000 AS result"),
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS month"),
                'wg_customer_absenteeism_indicator.diagnosticAll AS diagnosticNew',
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`employeeQuantity`) AS employeeQuantity"),
                DB::raw("COUNT(*) qty")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            /*->whereIn('wg_customer_absenteeism_indicator.id', function ($query) use ($criteria) {
                $query->select(DB::raw("MAX(id)"))
                    ->from('wg_customer_absenteeism_indicator')
                    ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
                    ->where('wg_customer_absenteeism_indicator.resolution', '0312')
                    ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"));
                if ($criteria != null) {
                    if ($criteria->mandatoryFilters != null) {
                        foreach ($criteria->mandatoryFilters as $item) {
                            if ($item->field == 'customerId') {
                                $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            }
                        }
                    }
                }
            })*/
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator")))
            ->mergeBindings($q1);

        $query
            ->groupBy('wg_customer_absenteeism_indicator.year')
            ->orderBy('wg_customer_absenteeism_indicator.year', "DESC");

        $this->applyCriteria($query, $criteria, ['customerId', 'year']);

        return $this->get($query, $criteria);
    }

    private function getQueryForUnion($indicator, $fieldValue, $fieldGoal)
    {
        return DB::table('wg_customer_absenteeism_indicator')
            ->leftjoin("wg_customer_absenteeism_indicator_target", function ($join) {
                $join->on('wg_customer_absenteeism_indicator_target.customer_id', '=', 'wg_customer_absenteeism_indicator.customer_id');
                $join->on('wg_customer_absenteeism_indicator_target.period', '=', 'wg_customer_absenteeism_indicator.period');
            })
            ->select(
                DB::raw("'$indicator' AS label"),
                "wg_customer_absenteeism_indicator.$fieldValue AS value",
                DB::raw("IFNULL(wg_customer_absenteeism_indicator_target.$fieldGoal, 0) AS goal")
            )
            ->limit(1);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->eventNumber = $entity->eventNumber;
        $entityModel->eventMortalNumber = $entity->eventMortalNumber;
        $entityModel->disabilityDays = $entity->disabilityDays;
        $entityModel->chargedDays = $entity->chargedDays;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entity;
    }

    public function batchUpdate($entity)
    {
        $authUser = $this->getAuthUser();

        $this->model
            ->where('period', $entity->periodCode)
            ->where('customer_id', $entity->customerId)
            ->update([
                'employeeQuantity' => $entity->employeeQuantity,
                'programedDays' => $entity->programedDays,
                'updatedBy' => $authUser ? $authUser->id : 1,
                'updated_at' => DB::raw('NOW()')
            ]);

        return $entity;
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
            return null;
        }
    }

    public function getChartEventNumber($criteria)
    {
        return $this->service->getChartEventNumber($criteria);
    }

    public function getCharttDisabilityDays($criteria)
    {
        return $this->service->getCharttDisabilityDays($criteria);
    }

    public function getCharttIF($criteria)
    {
        return $this->service->getCharttIF($criteria);
    }

    public function getCharttIS($criteria)
    {
        return $this->service->getCharttIS($criteria);
    }

    public function getCharttILI($criteria)
    {
        return $this->service->getCharttILI($criteria);
    }

    public function consolidate($id, $resolution)
    {
        $authUser = $this->getAuthUser();

        if ($this->shouldQueueConsolidate($id)) {
            $criteria = [
                "id" => $id,
                "resolution" => $resolution,
                "userId" => $authUser->id,
                "name" => $authUser->name,
                "email" => $authUser->email,
            ];
            Queue::push(CustomerAbsenteeismIndicatorJob::class, ['criteria' => $criteria], 'zip');

            return ["isSuccess" => true, "isQueue" => true];
        } else {
            return [
                "isSuccess" => $this->service->consolidate($id, $resolution, $authUser->id),
                "isQueue" => false
            ];
        }
    }

    public function getWorkplaceList($customerId)
    {
        return $this->service->getWorkplaceList($customerId);
    }

    public function getChartFrequencyAccidentality($criteria)
    {
        return $this->service->getChartFrequencyAccidentality($criteria);
    }

    public function getChartSeverityAccidentality($criteria)
    {
        return $this->service->getChartSeverityAccidentality($criteria);
    }

    public function getChartMortalProportionAccidentality($criteria)
    {
        return $this->service->getChartMortalProportionAccidentality($criteria);
    }

    public function getChartAbsenteeismMedicalCause($criteria)
    {
        return $this->service->getChartAbsenteeismMedicalCause($criteria);
    }

    public function getChartOccupationalDiseaseFatalityRate($criteria)
    {
        return $this->service->getChartOccupationalDiseaseFatalityRate($criteria);
    }

    public function getChartOccupationalDiseasePrevalence($criteria)
    {
        return $this->service->getChartOccupationalDiseasePrevalence($criteria);
    }

    public function getChartOccupationalDiseaseIncidence($criteria)
    {
        return $this->service->getChartOccupationalDiseaseIncidence($criteria);
    }



    public function exportExcelParent($criteria)
    {
        $data = $this->service->getExportParent($criteria);
        $filename = 'MATRIZ_DE_INDICADORES_AUSENTISMO_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Matriz', $data);
    }

    public function exportExcelFrequencyAccidentality($criteria)
    {
        $data = $this->service->getExportFrequencyAccidentalityData($criteria);
        $filename = 'ANALISIS_DE_FRECUENCIA_ACCIDENTALIDAD_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    public function exportExcelSeverityAccidentality($criteria)
    {
        $data = $this->service->getExportSeverityAccidentalityData($criteria);
        $filename = 'ANALISIS_DE_SEVERIDAD_ACCIDENTALIDAD_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    public function exportExcelMortalProportionAccidentality($criteria)
    {
        $data = $this->service->getExportMortalProportionAccidentalityData($criteria);
        $filename = 'ANALISIS_DE_PROPORCION_ACCIDENTES_MORTALES_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    public function exportExcelAbsenteeismMedicalCause($criteria)
    {
        $data = $this->service->getExportAbsenteeismMedicalCause($criteria);
        $filename = 'ANALISIS_DE_AUSENTISMO_CAUSA_MEDICA_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    public function exportExcelOccupationalDiseaseFatalityRate($criteria)
    {
        $data = $this->service->getExportOccupationalDiseaseFatalityRate($criteria);
        $filename = 'ANALISIS_DE_TASA_LETALIDAD_ENFERMEDAD_LABORAL_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    public function exportExcelOccupationalDiseasePrevalence($criteria)
    {
        $data = $this->service->getExportOccupationalDiseasePrevalence($criteria);
        $filename = 'ANALISIS_DE_PREVALENCIA_ENFERMEDAD_LABORAL_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    public function exportExcelOccupationalDiseaseIncidence($criteria)
    {
        $data = $this->service->getExportOccupationalDiseaseIncidence($criteria);
        $filename = 'ANALISIS_DE_INCIDENCIA_ENFERMEDAD_LABORAL_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Indicadores', $data);
    }

    private function shouldQueueConsolidate($id)
    {
        return SystemParameter::where("group", "wg_customer_consolidate_0312_job")
            ->where("value", $id)
            ->count() > 0;
    }
}
