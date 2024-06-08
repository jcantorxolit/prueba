<?php

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItem40595;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use AdeN\Api\Helpers\ExportHelper;

class CustomerRoadSafetyItem40595Service extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getCriterion($criteria)
    {
        $query = DB::table('wg_customer_road_safety_item_40595');

        /* Example relation*/
        $query->join('wg_customer_road_safety_40595', function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->join('wg_road_safety_item_40595', function ($join) {
            $join->on('wg_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
        })->join('wg_road_safety_40595', function ($join) {
            $join->on('wg_road_safety_40595.id', '=', 'wg_road_safety_item_40595.road_safety_id');
        })->join('wg_road_safety_cycle_40595', function ($join) {
            $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_road_safety_40595.cycle_id');
        })->join("wg_road_safety_item_criterion_40595", function ($join) {
            $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.totalEmployee', '=', 'wg_road_safety_item_criterion_40595.size');
            $join->on('wg_customers.id', '=', 'wg_customer_road_safety_40595.customer_id');
        })->leftjoin('wg_road_safety_rate_40595', function ($join) {
            $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
        })->select(
            'wg_road_safety_item_criterion_40595.id',
            'wg_road_safety_item_criterion_40595.description'
        );

        $query->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customer_road_safety_item_40595.id', $criteria->id);

        return $query->first();
    }

    public function getLegalFramework($criteria)
    {
        $qDetail = DB::table('wg_customer_road_safety_item_detail_40595')
            ->join('wg_customer_road_safety_item_40595', function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_detail_40595.customer_road_safety_item_id');
            })
            ->select(
                'wg_customer_road_safety_item_detail_40595.id',
                'wg_customer_road_safety_item_detail_40595.road_safety_item_detail_id',
                'wg_customer_road_safety_item_detail_40595.is_active',
                'wg_customer_road_safety_item_detail_40595.is_deleted'
            )
            ->where('wg_customer_road_safety_item_detail_40595.customer_road_safety_item_id', $criteria->id)
            ->where('wg_customer_road_safety_item_detail_40595.is_deleted', 0);

        /* Example relation*/
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
                $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_road_safety_item_detail_40595", function ($join) {
                $join->on('wg_road_safety_item_detail_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_road_safety_item_detail_40595.id', '=', 'detail.road_safety_item_detail_id');
            })
            ->select(
                DB::raw("IFNULL(detail.id, 0) AS id"),
                DB::raw("? AS customerRoadSafetyItemId"),
                'wg_road_safety_item_detail_40595.id AS roadSafetyItemDetailId',
                'wg_road_safety_item_detail_40595.description',
                'detail.is_active AS isActive'
            )
            ->addBinding($criteria->id, 'select')
            ->mergeBindings($qDetail)
            ->where('wg_road_safety_item_40595.id', $criteria->roadSafetyItemId)
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        return $query->get();
    }

    public function getVerificationList($criteria)
    {
        $qDetail = DB::table('wg_customer_road_safety_item_verification_40595')
            ->join('wg_customer_road_safety_item_40595', function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_verification_40595.customer_road_safety_item_id');
            })
            ->select(
                'wg_customer_road_safety_item_verification_40595.id',
                'wg_customer_road_safety_item_verification_40595.road_safety_item_criterion_id',
                'wg_customer_road_safety_item_verification_40595.is_active',
                'wg_customer_road_safety_item_verification_40595.is_deleted'
            )
            ->where('wg_customer_road_safety_item_verification_40595.customer_road_safety_item_id', $criteria->id)
            ->where('wg_customer_road_safety_item_verification_40595.is_deleted', 0);

        /* Example relation*/
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->join("wg_road_safety_item_criterion_40595", function ($join) {
                $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            // ->join("wg_road_safety_item_criterion_detail_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_detail_40595.road_safety_item_criterion_id', '=', 'wg_road_safety_item_criterion_40595.id');
            // })
            // ->join("wg_customers", function ($join) {
            //     $join->on('wg_customers.totalEmployee', '=', 'wg_road_safety_item_criterion_40595.size');
            // })
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_road_safety_item_criterion_40595.id', '=', 'detail.road_safety_item_criterion_id');
            })
            ->select(
                DB::raw("IFNULL(detail.id, 0) AS id"),
                DB::raw("? AS customerRoadSafetyItemId"),
                'wg_road_safety_item_criterion_40595.id AS roadSafetyItemCriterionId',
                'wg_road_safety_item_criterion_40595.description',
                'detail.is_active AS isActive'
            )
            ->addBinding($criteria->id, 'select')
            ->mergeBindings($qDetail)
            ->where('wg_road_safety_40595.id', $criteria->roadSafetyItemId)
            //->where('wg_customers.id', $criteria->customerRoadSafety->customerId)
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        $data = $query->get()->toArray();

        return array_map(function ($row) {
            $row->isActive = $row->isActive == 1;
            return $row;
        }, $data);
    }

    public function getQuestion($criteria)
    {
        $qDetail = DB::table('wg_customer_diagnostic')
            ->select(
                DB::raw("MAX(wg_customer_diagnostic.id) AS id"),
                'wg_customer_diagnostic.customer_id'
            )
            ->groupBy('wg_customer_diagnostic.customer_id');

        /* Example relation*/
        $query = DB::table('wg_customer_road_safety_40595')
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_40595.id');
            })
            ->join(DB::raw("wg_road_safety_item_40595"), function ($join) {
                $join->on('wg_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
            })
            ->join("wg_road_safety_item_question_40595", function ($join) {
                $join->on('wg_road_safety_item_question_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            ->join(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('detail.customer_id', '=', 'wg_customer_road_safety_40595.customer_id');
            })
            ->join("wg_customer_diagnostic_prevention", function ($join) {
                $join->on('wg_customer_diagnostic_prevention.question_id', '=', 'wg_road_safety_item_question_40595.program_prevention_question_id');
                $join->on('wg_customer_diagnostic_prevention.diagnostic_id', '=', 'detail.id');
            })
            ->join("wg_progam_prevention_question", function ($join) {
                $join->on('wg_progam_prevention_question.id', '=', 'wg_customer_diagnostic_prevention.question_id');
            })
            ->leftjoin("wg_rate", function ($join) {
                $join->on('wg_rate.id', '=', 'wg_customer_diagnostic_prevention.rate_id');
            })
            ->select(
                'wg_road_safety_item_question_40595.id',
                'wg_progam_prevention_question.description',
                'wg_progam_prevention_question.article',
                'wg_progam_prevention_question.id AS questionId',
                'wg_rate.text AS rate'
            )
            ->mergeBindings($qDetail)
            ->where('wg_customer_road_safety_item_40595.id', $criteria->id)
            ->where('wg_road_safety_item_40595.is_active', 1);

        return $query->get();
    }

    public function getLastComment($criteria)
    {
        $qDetail = DB::table('wg_customer_road_safety_item_comment_40595')
            ->select(
                DB::raw("MAX(wg_customer_road_safety_item_comment_40595.id) AS id"),
                'wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id'
            )
            ->where('wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id', $criteria->id)
            ->where('wg_customer_road_safety_item_comment_40595.type', 'A')
            ->groupBy('wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id');

        $query = DB::table('wg_customer_road_safety_item_comment_40595')
            ->join(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('detail.id', '=', 'wg_customer_road_safety_item_comment_40595.id');
            })
            ->select(
                'wg_customer_road_safety_item_comment_40595.id',
                'wg_customer_road_safety_item_comment_40595.comment'
            )
            ->mergeBindings($qDetail);

        return $query->first();
    }

    public function getExportData($criteria)
    {

        $query = DB::table('wg_customer_road_safety_item_40595');

        $query->join('wg_customer_road_safety_40595', function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->join('wg_road_safety_40595', function ($join) {
            $join->on('wg_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
        })->join('wg_road_safety_item_40595', function ($join) {
            $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
        })->join('wg_road_safety_cycle_40595', function ($join) {
            $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_road_safety_40595.cycle_id');
        })
        // ->join("wg_road_safety_item_criterion_40595", function ($join) {
        //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
        // })
        ->leftjoin('wg_road_safety_rate_40595', function ($join) {
            $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
        });

        $query
            ->select(
                'wg_road_safety_cycle_40595.name AS cycle',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.numeral',
                'wg_road_safety_40595.description',
                DB::raw("IFNULL(wg_road_safety_rate_40595.text, 'Sin Evaluar') AS rate"),
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_40595.id', $criteria->customerRoadSafetyId)
            ->orderBy('wg_road_safety_cycle_40595.id')
            ->orderBy('wg_road_safety_40595.numeral');


        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_road_safety_item_40595.rate_id', $criteria->rateId);
        }

        $heading = [
            "CICLO" => "cycle",
            "NUMERAL" => "numeral",
            "VARIABLE" => "description",
            "CALIFICACIÓN" => "rate"
        ];

        Log::info($query->toSql());
        Log::info("customerRoadSafetyId::" . $criteria->customerRoadSafetyId);

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportDataClosed($criteria)
    {
        $query = DB::table('wg_customer_road_safety_item_40595');

        $query->join('wg_customer_road_safety_40595', function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->join('wg_road_safety_item_40595', function ($join) {
            $join->on('wg_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
        })->join('wg_road_safety_40595', function ($join) {
            $join->on('wg_road_safety_40595.id', '=', 'wg_road_safety_item_40595.road_safety_id');
        })->join('wg_road_safety_cycle_40595', function ($join) {
            $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_road_safety_40595.cycle_id');
        })
        ->leftjoin('wg_road_safety_rate_40595', function ($join) {
            $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
        });

        $query
            ->select(
                'wg_road_safety_cycle_40595.name AS cycle',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_item_40595.numeral',
                'wg_road_safety_item_40595.description',
                DB::raw("IFNULL(wg_road_safety_rate_40595.text, 'N/A') AS rate"),
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_item_40595.is_freezed', 'activo')
            ->where('wg_customer_road_safety_40595.id', $criteria->customerRoadSafetyId)
            ->orderBy('wg_road_safety_cycle_40595.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_road_safety_item_40595.rate_id', $criteria->rateId);
        }

        $heading = [
            "CICLO" => "cycle",
            "CÓDIGO" => "abbreviation",
            "NUMERAL" => "numeral",
            "DESCRIPCIÓN" => "description",
            "CALIFICACIÓN" => "rate",
            "VALOR" => "value"
        ];

        //Log::info($query->toSql());
        //Log::info("customerRoadSafetyId::" . $criteria->customerRoadSafetyId);

        return ExportHelper::headings($query->get(), $heading);
    }
}
