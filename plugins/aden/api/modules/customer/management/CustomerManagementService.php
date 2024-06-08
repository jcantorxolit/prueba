<?php

namespace AdeN\Api\Modules\Customer\Management;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Classes\Criteria;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Illuminate\Support\Collection;
use Log;
use Str;

use function PHPSTORM_META\map;

class CustomerManagementService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function find($criteria)
    {
        $query = DB::table('wg_customer_management')
            ->join('wg_customer_management_program', function ($join) {
                $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');
            })
            ->join('wg_program_management_economic_sector', function ($join) {
                $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');
            })
            ->select(
                'wg_customer_management.id',
                'wg_customer_management_program.id as customerManagementProgramId',
                'wg_customer_management_program.program_economic_sector_id as programEconomicSectorId',
                'wg_customer_management_program.customer_workplace_id as customerWorkplaceId'
            )
            ->where('wg_program_management_economic_sector.program_id', $criteria->programId)
            ->where('wg_customer_management.customer_id', $criteria->customerId)
            ->where('wg_customer_management_program.customer_workplace_id', $criteria->customerWorkplaceId)
            ->where('wg_customer_management_program.active', $criteria->isActive)
            ->where('wg_customer_management.status', 'iniciado');

        if (isset($criteria->id) && $criteria->id > 0) {
            $query->where('wg_customer_management.id', $criteria->id);
        }

        return  $query->first();
    }

    public function isWorkplaceInOpenManagement($criteria)
    {
        $query = DB::table('wg_customer_management')
            ->join('wg_customer_management_program', function ($join) {
                $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');
            })
            ->join('wg_program_management_economic_sector', function ($join) {
                $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');
            })
            ->select(
                'wg_customer_management.id',
                'wg_customer_management_program.id as customerManagementProgramId',
                'wg_customer_management_program.program_economic_sector_id as programEconomicSectorId',
                'wg_customer_management_program.customer_workplace_id as customerWorkplaceId'
            )
            ->where('wg_customer_management.customer_id', $criteria->customerId)
            ->where('wg_customer_management.status', 'iniciado')
            ->where('wg_customer_management_program.customer_workplace_id', $criteria->customerWorkplaceId)
            ->where('wg_customer_management_program.active', $criteria->isActive);

        // if (isset($criteria->id) && $criteria->id > 0) {
        //     $query->where('wg_customer_management.id', $criteria->id);
        // }

        return  $query->count() > 0;
    }

    public function prepareQueryBase($criteria)
    {
        $query = DB::table('wg_program_management')
            ->join('wg_program_management_economic_sector', function ($join) {
                $join->on('wg_program_management_economic_sector.program_id', '=', 'wg_program_management.id');
            })
            ->join('wg_customer_management_program', function ($join) {
                $join->on('wg_customer_management_program.program_economic_sector_id', '=', 'wg_program_management_economic_sector.id');
            })
            ->join('wg_economic_sector', function ($join) {
                $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');
            })
            ->join('wg_customer_management', function ($join) {
                $join->on('wg_customer_management.id', '=', 'wg_customer_management_program.management_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_management_program.customer_workplace_id');
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_management.customer_id');
            })
            ->join('wg_program_management_category', function ($join) {
                $join->on('wg_program_management_category.program_id', '=', 'wg_program_management.id');
            })
            ->join('wg_program_management_question', function ($join) {
                $join->on('wg_program_management_question.category_id', '=', 'wg_program_management_category.id');
            });

        return $query;
    }

    public function prepareSubQueryBase($criteria)
    {
        $qInner = $this->prepareInnerQueryBase($criteria);

        $query = $this->prepareQueryBase($criteria);

        $query
            ->leftjoin(DB::raw("({$qInner->toSql()}) AS wg_customer_management_detail"), function ($join) {
                $join->on('wg_customer_management_detail.question_id', '=', 'wg_program_management_question.id');
                $join->on('wg_customer_management_detail.management_id', '=', 'wg_customer_management.id');
            })
            ->mergeBindings($qInner)
            ->select(
                'wg_program_management.id',
                'wg_program_management.name',
                'wg_program_management.abbreviation',
                'wg_program_management.isWeighted',
                DB::raw("COUNT(*) AS questions"),
                DB::raw("SUM(CASE WHEN wg_customer_management_detail.id IS NOT NULL THEN 1 ELSE 0 END) AS answers"),
                DB::raw("SUM( CASE WHEN wg_program_management.isWeighted AND wg_customer_management_detail.CODE IN ( 'cp', 'c' ) THEN wg_program_management_question.weightedValue ELSE wg_customer_management_detail.VALUE END ) total"),
                'wg_customer_config_workplace.name AS workplace',
                'wg_customer_config_workplace.id AS workplaceId',
                'wg_customer_management.customer_id AS customerId',
                'wg_customer_management.status AS status',
                'wg_program_management_economic_sector.program_id AS programId',
                DB::raw("YEAR(wg_customer_management.created_at) AS year")
            )
            ->groupBy(
                'wg_customer_management.customer_id',
                'wg_customer_management.id'
            );

        return $query;
    }

    public function prepareInnerQueryBase($criteria)
    {
        $query = DB::table('wg_customer_management_detail')
            ->join('wg_rate', function ($join) {
                $join->on('wg_rate.id', '=', 'wg_customer_management_detail.rate_id');
            })
            ->select(
                'wg_customer_management_detail.id',
                'wg_customer_management_detail.management_id',
                'wg_customer_management_detail.question_id',
                'wg_customer_management_detail.rate_id',
                'wg_customer_management_detail.status',
                'wg_rate.text',
                'wg_rate.value',
                'wg_rate.code'
            );

        if ($criteria != null) {
            if ($criteria instanceof Criteria) {
                if ($criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if ($item->field == 'customerId') {
                            $query->whereIn('wg_customer_management_detail.management_id', function ($query) use ($item) {
                                $query->select('id')
                                    ->from('wg_customer_management')
                                    ->where('wg_customer_management.customer_id', '=', SqlHelper::getPreparedData($item));
                            });
                        }
                    }
                }
            } else {
                if (property_exists($criteria, "customerId")) {
                    $customerId = CmsHelper::parseToStdClass([
                        "value" => $criteria->customerId,
                        "operator" => "eq"
                    ]);
                    $query->whereIn('wg_customer_management_detail.management_id', function ($query) use ($customerId) {
                        $query->select('id')
                            ->from('wg_customer_management')
                            ->where('wg_customer_management.customer_id', '=', SqlHelper::getPreparedData($customerId));
                    });
                }
            }
        }

        return $query;
    }

    public function getYearList($criteria)
    {
        $query = $this->prepareQueryBase($criteria);

        $query
            ->select(
                DB::raw("YEAR(wg_customer_management.created_at) AS item"),
                DB::raw("YEAR(wg_customer_management.created_at) AS value")
            )
            ->where('wg_customer_management.customer_id', $criteria->customerId)
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo')
            ->groupBy(
                'wg_customer_management.customer_id',
                DB::raw("YEAR(wg_customer_management.created_at)")
            )
            ->orderBy(DB::raw("YEAR(wg_customer_management.created_at)"), 'DESC');

        return $query->get();
    }

    public function getProgramList($criteria)
    {
        $query = $this->prepareQueryBase($criteria);

        $query
            ->select(
                'wg_program_management.id',
                'wg_program_management.name'
            )
            ->where('wg_customer_management.customer_id', $criteria->customerId)
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo')
            ->groupBy(
                'wg_customer_management.customer_id',
                'wg_program_management.id'
            )
            ->orderBy("wg_program_management.name");

        return $query->get();
    }

    public function getWorkplaceList($criteria)
    {
        $query = $this->prepareQueryBase($criteria);

        $query
            ->select(
                'wg_customer_config_workplace.name',
                'wg_customer_config_workplace.id',
                'wg_program_management.id AS programId'
            )
            ->where('wg_customer_management.customer_id', $criteria->customerId)
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo')
            ->groupBy(
                'wg_customer_management.customer_id',
                'wg_customer_config_workplace.id',
                'wg_program_management.id'
            )
            ->orderBy("wg_customer_config_workplace.name");

        return $query->get();
    }

    public function getWorkplaceByYears($criteria)
    {
        $query = $this->prepareQueryBase($criteria);

        $query
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                DB::raw("YEAR(wg_customer_management.created_at) AS period")
            )
            ->where('wg_customer_management.customer_id', $criteria->customerId)
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo')
            ->groupBy(
                'wg_customer_config_workplace.id',
                DB::raw("YEAR(wg_customer_management.created_at)")
            )
            ->orderBy("wg_customer_config_workplace.name");

        return $query->get();
    }

    public function getCategoryList($criteria)
    {
        $query = $this->prepareQueryBase($criteria);

        $query
            ->select(
                'wg_program_management_category.id',
                'wg_program_management_category.name'
            )
            ->where('wg_customer_management_program.management_id', $criteria->customerManagementId)
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo')
            ->where('wg_program_management_category.status', 'activo')
            ->groupBy(
                'wg_customer_management_program.management_id',
                'wg_program_management_category.id'
            )
            ->orderBy("wg_program_management_category.name");

        return $query->get();
    }

    public function getQuestionList($criteria)
    {
        $query = $this->prepareQueryBase($criteria);

        $query
            ->join('wg_customer_management_detail', function ($join) {
                $join->on('wg_customer_management_detail.management_id', '=', 'wg_customer_management.id');
                $join->on('wg_customer_management_detail.question_id', '=', 'wg_program_management_question.id');
            })
            ->select(
                'wg_customer_management_detail.id',
                DB::raw("SUBSTRING(TRIM(wg_program_management_question.description), 1, 250) AS description"),
                'wg_program_management_question.article',
                'wg_program_management_category.id AS categoryId'
            )
            ->where('wg_customer_management_program.management_id', $criteria->customerManagementId)
            //->where('wg_program_management_category.id', $criteria->categoryId)
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo')
            ->where('wg_program_management_category.status', 'activo')

            ->orderBy("wg_program_management_question.description");

        return $query->get();
    }

    public function getAvegareProgramChartBar($criteria)
    {
        $qBase = $this->prepareSubQueryBase($criteria);

        $query = DB::table(DB::raw("({$qBase->toSql()}) as indicator"))
            ->select(
                "indicator.workplace AS label",
                DB::raw("ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END), 0 ), 2 ) AS value")
            )
            ->mergeBindings($qBase)
            ->where("indicator.year", $criteria->year)
            ->where("indicator.programId", $criteria->programId)
            ->where("indicator.customerId", $criteria->customerId)
            ->where('indicator.status', '<>', 'cancelado');

        if (isset($criteria->workplaceList) && is_array($criteria->workplaceList)) {
            $query->whereIn('indicator.workplaceId', $criteria->workplaceList);
        }

        $config = array(
            "labelColumn" => ['Centro de Trabajo'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($query->get(), $config);
    }

    public function getImprovementPlanStatusChartBar($criteria)
    {
        $qBase = $this->prepareQueryBase($criteria);

        $qBase->join('wg_customer_management_detail', function ($join) {
            $join->on('wg_customer_management_detail.management_id', '=', 'wg_customer_management.id');
            $join->on('wg_customer_management_detail.question_id', '=', 'wg_program_management_question.id');
        })->join('wg_customer_improvement_plan', function ($join) {
            $join->on('wg_customer_improvement_plan.customer_id', '=', 'wg_customer_management.customer_id');
            $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_management_detail.id');
            $join->where('wg_customer_improvement_plan.entityName', '=', 'PE');
        })->select(
            'wg_customer_improvement_plan.status',
            'wg_customer_config_workplace.name AS workplace',
            'wg_customer_config_workplace.id AS workplaceId',
            'wg_customer_management.customer_id AS customerId',
            'wg_program_management_economic_sector.program_id AS programId',
            DB::raw("YEAR(wg_customer_management.created_at) AS year")
        )->where('wg_customer_management.status', '<>', 'cancelado');

        $query = DB::table(DB::raw("({$qBase->toSql()}) as indicator"))
            ->select(
                "indicator.workplace AS label",
                DB::raw("SUM(CASE WHEN indicator.status = 'AB' THEN 1 ELSE 0 END) AS open"),
                DB::raw("SUM(CASE WHEN indicator.status = 'CO' THEN 1 ELSE 0 END) AS completed"),
                DB::raw("SUM(CASE WHEN indicator.status = 'CA' THEN 1 ELSE 0 END) AS canceled")
            )
            ->mergeBindings($qBase)
            ->where("indicator.year", $criteria->year)
            ->where("indicator.programId", $criteria->programId)
            ->where("indicator.customerId", $criteria->customerId)
            ->groupBy(
                'indicator.customerId',
                'indicator.programId',
                'indicator.year',
                'indicator.workplaceId'
            );

        if (isset($criteria->workplaceList) && is_array($criteria->workplaceList)) {
            $query->whereIn('indicator.workplaceId', $criteria->workplaceList);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Abierta', 'field' => 'open', 'color' => '#5cb85c'],
                ['label' => 'Completada', 'field' => 'completed', 'color' => '#46b8da'],
                ['label' => 'Cancelada', 'field' => 'canceled', 'color' => '#d43f3a'],
            ]
        );

        return $this->chart->getChartBar($query->get(), $config);
    }

    public function getValorationChartBar($criteria)
    {
        $pivotList = $this->getWorkplacePivotList($criteria);

        $qBase = $this->prepareQueryBase($criteria);
        $qUnionFrom = $this->prepareRatePivotUnionQuery();

        $qBase
            ->join('wg_customer_management_detail', function ($join) {
                $join->on('wg_customer_management_detail.management_id', '=', 'wg_customer_management.id');
                $join->on('wg_customer_management_detail.question_id', '=', 'wg_program_management_question.id');
            })
            ->leftjoin('wg_rate', function ($join) {
                $join->on('wg_rate.id', '=', 'wg_customer_management_detail.rate_id');
            })
            ->select(
                'wg_rate.code',
                'wg_customer_config_workplace.name AS workplace',
                'wg_customer_config_workplace.id AS workplaceId',
                'wg_customer_management.customer_id AS customerId',
                'wg_program_management_economic_sector.program_id AS programId',
                DB::raw("YEAR(wg_customer_management.created_at) AS year")
            )
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->whereYear("wg_customer_management.created_at", '=', $criteria->year)
            ->where("wg_program_management_economic_sector.program_id", $criteria->programId)
            ->where("wg_customer_management.customer_id", $criteria->customerId);

        if (isset($criteria->workplaceList) && is_array($criteria->workplaceList)) {
            $qBase->whereIn('wg_customer_config_workplace.id', $criteria->workplaceList);
        }

        $query = DB::table(DB::raw("({$qUnionFrom->toSql()}) as wg_rate"))
            ->leftjoin(DB::raw("({$qBase->toSql()}) as indicator"), function ($join) {
                $join->on('indicator.code', '<=>', 'wg_rate.code');
            })
            ->select(
                "wg_rate.text AS label"
            )
            ->mergeBindings($qUnionFrom)
            ->mergeBindings($qBase)
            ->groupBy(
                'indicator.customerId',
                'indicator.programId',
                'indicator.year',
                'wg_rate.code'
            );

        foreach ($pivotList as $pivot) {
            $query->addSelect(
                DB::raw("SUM(CASE WHEN indicator.workplaceId = '{$pivot->id}' THEN 1 ELSE 0 END) AS '{$pivot->name}'")
            );
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => (new Collection($pivotList))->map(function ($item) {
                return ['label' => $item->name, 'field' => $item->name];
            })
        );

        trace_sql();

        return $this->chart->getChartLine($query->get(), $config);
    }

    private function prepareRatePivotUnionQuery()
    {
        $q1 = DB::table('wg_rate')->select('text', 'code');
        $q2 = DB::table('wg_config_general')->select('name', 'value')->where('wg_config_general.type', 'RATE_PIVOT');
        $q1->union($q2)->mergeBindings($q2);
        return $q1;
    }

    private function getWorkplacePivotList($criteria)
    {
        $query = DB::table('wg_program_management')
            ->join('wg_program_management_economic_sector', function ($join) {
                $join->on('wg_program_management_economic_sector.program_id', '=', 'wg_program_management.id');
            })
            ->join('wg_customer_management_program', function ($join) {
                $join->on('wg_customer_management_program.program_economic_sector_id', '=', 'wg_program_management_economic_sector.id');
            })
            ->join('wg_customer_management', function ($join) {
                $join->on('wg_customer_management.id', '=', 'wg_customer_management_program.management_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_management_program.customer_workplace_id');
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_management.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.name',
                'wg_customer_config_workplace.id'
            )
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->whereYear("wg_customer_management.created_at", '=', $criteria->year)
            ->where("wg_program_management_economic_sector.program_id", $criteria->programId)
            ->where("wg_customer_management.customer_id", $criteria->customerId)
            ->groupBy(
                'wg_customer_management.customer_id',
                'wg_program_management_economic_sector.program_id',
                DB::raw("YEAR(wg_customer_management.created_at)"),
                'wg_customer_config_workplace.id'
            );

        if (isset($criteria->workplaceList) && is_array($criteria->workplaceList)) {
            $query->whereIn('wg_customer_config_workplace.id', $criteria->workplaceList);
        }

        return $query->get();
    }

    public function getChartBar($criteria)
    {
        $sql = "select pp.`name`, pp.`abbreviation`, pp.color, pp.highlightColor
                    , sum(case when ISNULL(wr.`code`) then 1 else 0 end) nocontesta
                    , sum(case when wr.`code` = 'c' then 1 else 0 end) cumple
                    , sum(case when wr.`code` = 'cp' then 1 else 0 end) parcial
                    , sum(case when wr.`code` = 'nc' then 1 else 0 end) nocumple
                    , sum(case when wr.`code` = 'na' then 1 else 0 end) noaplica
                from wg_program_management pp
                INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
                inner join wg_customer_management_detail dp on ppq.id 	= dp.question_id
                left join wg_rate wr on dp.rate_id = wr.id
                where dp.management_id = :management_id and cmp.active = 1 and cmp.management_id = :managementId
                group by pp.`name`
                order by pp.id";

        $data = DB::select($sql, [
            'management_id' => $criteria->managementId,
            'managementId' => $criteria->managementId,
        ]);

        $rates = DB::table('wg_rate')->get();

        $config = array(
            "labelColumn" => 'abbreviation',
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
        $sql = "select programa.name label
        , ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS value
        , programa.color, programa.highlightColor
        from(
                                select  pp.id program_id, pp.`name`, pp.color, pp.highlightColor,count(*) questions
                                , pp.isWeighted
                                , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                , SUM( CASE WHEN pp.isWeighted AND cdp.code IN ( 'cp', 'c' ) THEN ppq.weightedValue ELSE cdp.value END ) total 
                                from wg_program_management pp
                                INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                                INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                                INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                                INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                                INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                                INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                                INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
                                left join (
                                                        select wg_customer_management_detail.*, wg_rate.text, wg_rate.value, wg_rate.code from wg_customer_management_detail
                                                        inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                                                        where management_id = :management_id_1
                                                        ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :management_id_2
                                group by  pp.`name`, pp.id
        )programa
        order by 1";

        $data = DB::select($sql, [
            'management_id_1' => $criteria->managementId,
            'management_id_2' => $criteria->managementId,
        ]);

        return $this->chart->getChartPie($data);
    }

    public function getStats($criteria)
    {
        $sql = "SELECT
        programa.management_id,
        questions,
        answers,
        ROUND(IFNULL(((answers / questions) * 100),0),2) advance,
        ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average,
        ROUND(IFNULL(total, 0), 2) total
    FROM
        (
            SELECT
                cdp.management_id,
                pp.isWeighted,
                COUNT(*) questions,
                SUM(CASE WHEN ISNULL(cdp.id) THEN 0 ELSE 1 END) answers,
                SUM( CASE WHEN pp.isWeighted AND cdp.code IN ( 'cp', 'c' ) THEN ppq.weightedValue ELSE cdp.value END ) total 
            FROM
                wg_program_management pp
                INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
            LEFT JOIN (
                SELECT
                    wg_customer_management_detail.*, wg_rate.text,
                    wg_rate.`value`,
                    wg_rate.`code`
                FROM
                    wg_customer_management_detail
                INNER JOIN wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                WHERE
                    management_id = :management_id_1
            ) cdp ON ppq.id = cdp.question_id
            WHERE
                pp.`status` = 'activo'
            AND ppc.`status` = 'activo'
            AND ppq.`status` = 'activo'
            AND cmp.active = 1
            AND cmp.management_id = :management_id_2
        ) programa";

        $data = DB::select($sql, [
            'management_id_1' => $criteria->managementId,
            'management_id_2' => $criteria->managementId,
        ]);

        return count($data) > 0 ? $data[0] : null;
    }

    public function getPrograms($managementId)
    {
        $sql = "SELECT
        programa.id,
        `name`,
        `workplace`,
        `economicSector`,
        abbreviation,
        questions,
        answers,
        round((answers / questions) * 100, 2) advance,
        ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average,
        total
    FROM
        (
            SELECT
                pp.id,
                wg_customer_config_workplace.`name` as workplace,
                ec.`name` as economicSector,
                pp.`name`,
                pp.abbreviation,
                pp.isWeighted,
                count(*) questions,
                SUM(CASE WHEN ISNULL(cdp.id) THEN 0 ELSE 1 END) answers,
                SUM( CASE WHEN pp.isWeighted AND cdp.code IN ( 'cp', 'c' ) THEN ppq.weightedValue ELSE cdp.value END ) total 
            FROM
                wg_program_management pp
                INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
            LEFT JOIN (
                SELECT
                    wg_customer_management_detail.*, wg_rate.text,
                    wg_rate.`value`,
                    wg_rate.`code`
                FROM
                    wg_customer_management_detail
                INNER JOIN wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                WHERE
                    management_id = :management_id_1
            ) cdp ON ppq.id = cdp.question_id
            WHERE
                pp.`status` = 'activo'
            AND ppc.`status` = 'activo'
            AND ppq.`status` = 'activo'
            AND cmp.active = 1
            AND cmp.management_id = :management_id_2
            GROUP BY
                pp.`name`,
                pp.id
        ) programa
    ORDER BY 1";

        return DB::select($sql, [
            'management_id_1' => $managementId,
            'management_id_2' => $managementId,
        ]);
    }

    public function getProgramsWithRateAndPercent(int $customerId, $period, $workplaceId)
    {
        return DB::table('wg_program_management as pp')
            ->join('wg_program_management_economic_sector as pec', 'pec.program_id', '=', 'pp.id')
            ->join('wg_economic_sector as ec', 'ec.id', '=', 'pec.economic_sector_id')
            ->join('wg_customer_management_program as cmp', function($join) {
                $join->on('cmp.program_economic_sector_id', '=', 'pec.id');
                $join->where('cmp.active', true);
            })
            ->join('wg_customer_management as mp', 'mp.id', '=', 'cmp.management_id')
            ->join('wg_customer_config_workplace as workplace', function($join) {
                $join->on('workplace.id', 'cmp.customer_workplace_id');
                $join->on('workplace.customer_id', 'mp.customer_id');
            })
            ->join('wg_program_management_category as ppc', 'ppc.program_id', '=', 'pp.id')
            ->join('wg_program_management_question as ppq', 'ppq.category_id', '=', 'ppc.id')
            ->join('wg_customer_management_detail as dp', function($join) {
                $join->on('dp.question_id', 'ppq.id');
                $join->on('dp.management_id', 'mp.id');
            })
            ->leftJoin('wg_rate as wr', 'wr.id', '=', 'dp.rate_id')
            ->where('mp.customer_id', $customerId)
            ->when($period, function($query) use ($period) {
                $query->whereYear('mp.created_at', $period);
            })
            ->when($workplaceId, function ($query) use ($workplaceId) {
                $query->where('workplace.id', $workplaceId);
            })
            ->groupBy('mp.id')
            ->select(
                'pp.name', 'pp.abbreviation',
                DB::raw("ROUND(SUM(CASE WHEN ISNULL(wr.`code`) THEN 1 ELSE 0 END) / count(ppq.id) * 100, 2) nocontesta"),
                DB::raw("ROUND(SUM(CASE WHEN wr.`code` = 'c' THEN 1 ELSE 0 END) / count(ppq.id) * 100, 2) cumple"),
                DB::raw("ROUND(SUM(CASE WHEN wr.`code` = 'cp' THEN 1 ELSE 0 END) / count(ppq.id) * 100, 2) parcial"),
                DB::raw("ROUND(SUM(CASE WHEN wr.`code` = 'nc' THEN 1 ELSE 0 END) / count(ppq.id) * 100, 2) nocumple"),
                DB::raw("ROUND(SUM(CASE WHEN wr.`code` = 'na' THEN 1 ELSE 0 END) / count(ppq.id) * 100, 2) noaplica"),
                DB::raw("sum(wr.value) as total"),
                DB::raw("count(ppq.id) as questions"),
                DB::raw("ROUND(IFNULL((sum(wr.value) / count(ppq.id)), 0), 2) as average")
            )
            ->get();
    }

}
