<?php

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem0312;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use AdeN\Api\Helpers\ExportHelper;

class CustomerEvaluationMinimumStandardItem0312Service extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getCriterion($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312');

        /* Example relation*/
        $query->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
        })->join('wg_minimum_standard_item_0312', function ($join) {
            $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
        })->join('wg_minimum_standard_0312', function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');
        })->join('wg_config_minimum_standard_cycle_0312', function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
        })->join("wg_minimum_standard_item_criterion_0312", function ($join) {
            $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
            $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            $join->on('wg_customers.id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
        })->leftjoin('wg_config_minimum_standard_rate_0312', function ($join) {
            $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
        })->select(
            'wg_minimum_standard_item_criterion_0312.id',
            'wg_minimum_standard_item_criterion_0312.description'
        );

        $query->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.id', $criteria->id);

        return $query->first();
    }

    public function getLegalFramework($criteria)
    {
        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_detail_0312')
            ->join('wg_customer_evaluation_minimum_standard_item_0312', function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_detail_0312.customer_evaluation_minimum_standard_item_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_detail_0312.id',
                'wg_customer_evaluation_minimum_standard_item_detail_0312.minimum_standard_item_detail_id',
                'wg_customer_evaluation_minimum_standard_item_detail_0312.is_active',
                'wg_customer_evaluation_minimum_standard_item_detail_0312.is_deleted'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_detail_0312.customer_evaluation_minimum_standard_item_id', $criteria->id)
            ->where('wg_customer_evaluation_minimum_standard_item_detail_0312.is_deleted', 0);

        /* Example relation*/
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_detail_0312", function ($join) {
                $join->on('wg_minimum_standard_item_detail_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_minimum_standard_item_detail_0312.id', '=', 'detail.minimum_standard_item_detail_id');
            })
            ->select(
                DB::raw("IFNULL(detail.id, 0) AS id"),
                DB::raw("? AS customerEvaluationMinimumStandardItemId"),
                'wg_minimum_standard_item_detail_0312.id AS minimumStandardItemDetailId',
                'wg_minimum_standard_item_detail_0312.description',
                'detail.is_active AS isActive'
            )
            ->addBinding($criteria->id, 'select')
            ->mergeBindings($qDetail)
            ->where('wg_minimum_standard_item_0312.id', $criteria->minimumStandardItemId)
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query->get();
    }

    public function getVerificationMode($criteria)
    {
        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_verification_0312')
            ->join('wg_customer_evaluation_minimum_standard_item_0312', function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_verification_0312.customer_evaluation_minimum_standard_item_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_verification_0312.id',
                'wg_customer_evaluation_minimum_standard_item_verification_0312.minimum_standard_item_criterion_detail_id',
                'wg_customer_evaluation_minimum_standard_item_verification_0312.is_active',
                'wg_customer_evaluation_minimum_standard_item_verification_0312.is_deleted'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_verification_0312.customer_evaluation_minimum_standard_item_id', $criteria->id)
            ->where('wg_customer_evaluation_minimum_standard_item_verification_0312.is_deleted', 0);

        /* Example relation*/
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_detail_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_detail_0312.minimum_standard_item_criterion_id', '=', 'wg_minimum_standard_item_criterion_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_minimum_standard_item_criterion_detail_0312.id', '=', 'detail.minimum_standard_item_criterion_detail_id');
            })
            ->select(
                DB::raw("IFNULL(detail.id, 0) AS id"),
                DB::raw("? AS customerEvaluationMinimumStandardItemId"),
                'wg_minimum_standard_item_criterion_detail_0312.id AS minimumStandardItemCriterionDetailId',
                'wg_minimum_standard_item_criterion_detail_0312.description',
                'detail.is_active AS isActive'
            )
            ->addBinding($criteria->id, 'select')
            ->mergeBindings($qDetail)
            ->where('wg_minimum_standard_item_0312.id', $criteria->minimumStandardItemId)
            ->where('wg_customers.id', $criteria->customerEvaluationMinimumStandard->customerId)
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

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
        $query = DB::table('wg_customer_evaluation_minimum_standard_0312')
            ->join("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->join("wg_minimum_standard_item_question_0312", function ($join) {
                $join->on('wg_minimum_standard_item_question_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('detail.customer_id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
            })
            ->join("wg_customer_diagnostic_prevention", function ($join) {
                $join->on('wg_customer_diagnostic_prevention.question_id', '=', 'wg_minimum_standard_item_question_0312.program_prevention_question_id');
                $join->on('wg_customer_diagnostic_prevention.diagnostic_id', '=', 'detail.id');
            })
            ->join("wg_progam_prevention_question", function ($join) {
                $join->on('wg_progam_prevention_question.id', '=', 'wg_customer_diagnostic_prevention.question_id');
            })
            ->leftjoin("wg_rate", function ($join) {
                $join->on('wg_rate.id', '=', 'wg_customer_diagnostic_prevention.rate_id');
            })
            ->select(
                'wg_minimum_standard_item_question_0312.id',
                'wg_progam_prevention_question.description',
                'wg_progam_prevention_question.article',
                'wg_progam_prevention_question.id AS questionId',
                'wg_rate.text AS rate'
            )
            ->mergeBindings($qDetail)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.id', $criteria->id)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query->get();
    }

    public function getLastComment($criteria)
    {
        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_comment_0312')
            ->select(
                DB::raw("MAX(wg_customer_evaluation_minimum_standard_item_comment_0312.id) AS id"),
                'wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id', $criteria->id)
            ->where('wg_customer_evaluation_minimum_standard_item_comment_0312.type', 'A')
            ->groupBy('wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id');

        $query = DB::table('wg_customer_evaluation_minimum_standard_item_comment_0312')
            ->join(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('detail.id', '=', 'wg_customer_evaluation_minimum_standard_item_comment_0312.id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_comment_0312.id',
                'wg_customer_evaluation_minimum_standard_item_comment_0312.comment'
            )
            ->mergeBindings($qDetail);

        return $query->first();
    }

    public function getExportData($criteria)
    {

        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312');

        $query->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
        })->join('wg_minimum_standard_item_0312', function ($join) {
            $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
        })->join('wg_minimum_standard_0312', function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');
        })->join('wg_config_minimum_standard_cycle_0312', function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
        })->join("wg_minimum_standard_item_criterion_0312", function ($join) {
            $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
            $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            $join->on('wg_customers.id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
        })->leftjoin('wg_config_minimum_standard_rate_0312', function ($join) {
            $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
        });

        $query
            ->select(
                'wg_config_minimum_standard_cycle_0312.name AS cycle',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_item_0312.numeral',
                'wg_minimum_standard_item_0312.description',
                DB::raw("IFNULL(wg_config_minimum_standard_rate_0312.text, 'N/A') AS rate"),
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_0312.id', $criteria->customerEvaluationMinimumStandardId)
            ->orderBy('wg_config_minimum_standard_cycle_0312.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_evaluation_minimum_standard_item_0312.rate_id', $criteria->rateId);
        }

        $heading = [
            "CICLO" => "cycle",
            "CÓDIGO" => "abbreviation",
            "NUMERAL" => "numeral",
            "DESCRIPCIÓN" => "description",
            "CALIFICACIÓN" => "rate",
            "VALOR" => "value"
        ];

        Log::info($query->toSql());
        Log::info("customerEvaluationMinimumStandardId::" . $criteria->customerEvaluationMinimumStandardId);

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportDataClosed($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312');

        $query->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
        })->join('wg_minimum_standard_item_0312', function ($join) {
            $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
        })->join('wg_minimum_standard_0312', function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');
        })->join('wg_config_minimum_standard_cycle_0312', function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
        })
        ->leftjoin('wg_config_minimum_standard_rate_0312', function ($join) {
            $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
        });

        $query
            ->select(
                'wg_config_minimum_standard_cycle_0312.name AS cycle',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_item_0312.numeral',
                'wg_minimum_standard_item_0312.description',
                DB::raw("IFNULL(wg_config_minimum_standard_rate_0312.text, 'N/A') AS rate"),
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_0312.id', $criteria->customerEvaluationMinimumStandardId)
            ->orderBy('wg_config_minimum_standard_cycle_0312.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_evaluation_minimum_standard_item_0312.rate_id', $criteria->rateId);
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
        //Log::info("customerEvaluationMinimumStandardId::" . $criteria->customerEvaluationMinimumStandardId);

        return ExportHelper::headings($query->get(), $heading);
    }
}
