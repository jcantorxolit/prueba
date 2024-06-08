<?php

namespace AdeN\Api\Modules\Customer\ManagementDetail;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Log;
use AdeN\Api\Helpers\ExportHelper;
use Carbon\Carbon;

class CustomerManagementDetailService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getExportData($criteria)
    {
        $qDetail = DB::table('wg_customer_management_detail');
        $qDetail->join('wg_rate', function ($join) {
            $join->on('wg_customer_management_detail.rate_id', '=', 'wg_rate.id');

        })->select('wg_customer_management_detail.*', 'wg_rate.text', 'wg_rate.value', 'wg_rate.code');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'managementId') {
                        $qDetail->where(SqlHelper::getPreparedField('management_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_customer_management_program');
        $qSub->join("wg_customer_management", function ($join) {
            $join->on('wg_customer_management.id', '=', 'wg_customer_management_program.management_id');

        })->join("wg_program_management_economic_sector", function ($join) {
            $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');

        })->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');

        })->join('wg_program_management', function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');

        })->join('wg_program_management_category', function ($join) {
            $join->on('wg_program_management_category.program_id', '=', 'wg_program_management.id');

        })->join('wg_program_management_question', function ($join) {
            $join->on('wg_program_management_category.id', '=', 'wg_program_management_question.category_id');

        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_management_program.customer_workplace_id');
            $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_management.customer_id');

        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('wg_program_management_question.id', '=', 'detail.question_id');

        })->select(
            'wg_program_management_category.id',
            'wg_program_management_category.name',
            'wg_program_management_economic_sector.program_id',
            'wg_program_management.name AS program_name',
            'wg_customer_management_program.management_id',
            'wg_program_management.isWeighted',
            DB::raw('COUNT(*) AS questions'),
            DB::raw('SUM(CASE WHEN ISNULL(detail.id) THEN 0 ELSE 1 END) AS answers'),
            //DB::raw('SUM(detail.`value`) total'),
            DB::raw("SUM( CASE WHEN wg_program_management.isWeighted AND detail.code IN ('cp', 'c') THEN wg_program_management_question.weightedValue ELSE  detail.value END ) total")
        )
            ->whereRaw("wg_customer_management_program.`active` = '1'")
            ->whereRaw("wg_program_management.`status` = 'activo'")
            ->whereRaw("wg_program_management_category.`status` = 'activo'")
            ->whereRaw("wg_program_management_question.`status` = 'activo'")
            ->groupBy('wg_program_management_category.name', 'wg_program_management_category.id')
            ->mergeBindings($qDetail);

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'managementId') {
                        $qSub->where(SqlHelper::getPreparedField('wg_customer_management_program.management_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $queryHeader = DB::table(DB::raw("({$qSub->toSql()}) as category"))
            ->select(
                'category.id',
                'category.name',
                DB::raw("SUM(questions) AS questions"),
                DB::raw("SUM(answers) AS answers"),
                DB::raw("ROUND(IFNULL(SUM((answers / questions) * 100), 0), 2) AS advance"),
                DB::raw("ROUND(IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END), 0 ), 2 ) AS average"),
                DB::raw("ROUND(IFNULL(SUM(total), 0), 2) AS total"),
                'category.program_id',
                'category.program_name',
                'category.management_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'programId') {
                        $queryHeader->whereRaw(SqlHelper::getPreparedField("category.program_id") . ' = ' . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $queryHeader->groupBy('category.id')->mergeBindings($qSub);

        $query = DB::table(DB::raw("({$queryHeader->toSql()}) as header"))
            ->join('wg_customer_management', function ($join) {
                $join->on('wg_customer_management.id', '=', 'header.management_id');

            })
            ->join('wg_customer_management_detail', function ($join) {
                $join->on('wg_customer_management.id', '=', 'wg_customer_management_detail.management_id');

            })
            ->join('wg_program_management_question', function ($join) {
                $join->on('wg_program_management_question.id', '=', 'wg_customer_management_detail.question_id');

            })
            ->join('wg_program_management_category', function ($join) {
                $join->on('wg_program_management_category.id', '=', 'wg_program_management_question.category_id');
                $join->on('wg_program_management_category.id', '=', 'header.id');

            })
            ->join('wg_customer_management_program', function ($join) {
                //$join->on('wg_customer_management_program.program_id', '=', 'wg_program_management_category.program_id');
                $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');

            })
            ->join("wg_program_management_economic_sector", function ($join) {
                $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');
    
            })
            ->join("wg_economic_sector", function ($join) {
                $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');
    
            })
            ->join('wg_program_management', function ($join) {
                $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');
                $join->on('wg_program_management.id', '=', 'wg_program_management_category.program_id');
    
            })
            ->leftjoin('wg_rate', function ($join) {
                $join->on('wg_rate.id', '=', 'wg_customer_management_detail.rate_id');

            })
            ->select(
                'header.program_name',
                'header.questions',
                'header.answers',
                'header.advance',
                'header.average',
                'header.total',
                'header.name',
                'wg_program_management_question.description',
                'wg_program_management_question.article',
                'wg_rate.text AS rate_text'
            )
            ->whereRaw("wg_customer_management_program.active = 1")
            ->whereRaw("wg_program_management_category.status = 'activo'")
            ->whereRaw("wg_program_management_question.status = 'activo'")
            ->mergeBindings($queryHeader);

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'programId') {
                        $query->whereRaw(SqlHelper::getPreparedField("wg_program_management_economic_sector.program_id") . ' = ' . SqlHelper::getPreparedData($item));
                    } else if ($item->field == 'managementId') {
                        $query->whereRaw(SqlHelper::getPreparedField('wg_customer_management_detail.management_id') . ' = ' . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $result = $query->get();

        $heading = [
            "PROGRAMA EMPRESARIAL" => "program_name",
            "PREGUNTAS" => "questions",
            "RESPUESTAS" => "answers",
            "AVANCE" => "advance",
            "PROMEDIO" => "average",
            //"TOTAL" => "total",
            "CATEGORIA" => "name",
            "DESCRIPCIÓN" => "description",
            "ARTÍCULO" => "article",
            "CALIFICACIÓN" => "rate_text",
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getResourceList($criteria, $resourceClassName)
    {
        $query = DB::table('wg_customer_management_detail')
            ->join('wg_program_management_question_resource', function ($join) {
                $join->on('wg_program_management_question_resource.management_question_id', '=', 'wg_customer_management_detail.question_id');

            })
            ->select(
                'wg_program_management_question_resource.id',
                'wg_program_management_question_resource.title',
                'wg_program_management_question_resource.description',
                'wg_program_management_question_resource.type',
                'wg_program_management_question_resource.url',
                'wg_program_management_question_resource.created_at AS createdAt'
            )
            ->where("wg_customer_management_detail.id", $criteria->managementDetailId)
            ->orderBy("wg_program_management_question_resource.id", 'DESC');

        return array_map(function($item) use ($resourceClassName) {
            $item->hasFile = false;
            $item->createdAt = $item->createdAt ? Carbon::parse($item->createdAt)->timezone('America/Bogota')->format('d-m-Y H:m') : null;
            if ($item->type == 'file') {
                $modelInstance = new $resourceClassName;
                if ($entity = $modelInstance->find($item->id)) {
                    $document = $entity->document;                    
                    $item->hasFile = $document != null;                    
                }
            }
            return $item;
        }, $query->get()->toArray());
    }
}