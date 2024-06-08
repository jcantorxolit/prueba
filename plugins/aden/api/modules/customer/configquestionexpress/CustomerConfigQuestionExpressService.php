<?php

namespace AdeN\Api\Modules\Customer\ConfigQuestionExpress;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Illuminate\Support\Collection;

class CustomerConfigQuestionExpressService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function bulkInsertQuestions($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $query =  $this->prepareBaseQuery($criteria)
            ->leftjoin("wg_customer_config_question_express", function ($join) {
                $join->on('wg_customer_config_question_express.customer_id', '=', 'wg_customers.id');
                $join->on('wg_customer_config_question_express.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_question_express.question_express_id', '=', 'wg_config_question_express.id');
            })
            ->select(
                DB::raw("NULL AS id"),
                DB::raw("wg_customers.id AS customer_id"),
                "wg_customer_config_workplace.id AS customer_workplace_id",
                "wg_config_question_express.id AS question_express_id",
                DB::raw("NULL AS rate"),
                DB::raw("1 AS is_active"),
                DB::raw("NOW() AS created_at"),
                DB::raw("users.id AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("users.id AS updated_by")
            )
            ->whereNull('wg_customer_config_question_express.id');

        $sql = 'INSERT INTO wg_customer_config_question_express (`id`, `customer_id`, `customer_workplace_id`, `question_express_id`, `rate`, `is_active`, `created_at`, `updated_at`, `created_by`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkInactiveQuestions($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $qDetail = $qDetail = $this->prepareBaseQuery($criteria);

        return DB::table('wg_customer_config_question_express')
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as wg_config_question_express"), function ($join) {
                $join->on('wg_config_question_express.customer_id', '=', 'wg_customer_config_question_express.customer_id');
                $join->on('wg_config_question_express.customer_workplace_id', '=', 'wg_customer_config_question_express.customer_workplace_id');
                $join->on('wg_config_question_express.question_express_id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->mergeBindings($qDetail)
            ->whereNull("wg_config_question_express.question_express_id")
            ->update([
                "wg_customer_config_question_express.is_active" => 0,
                "wg_customer_config_question_express.updated_by" => $criteria->userId
            ]);
    }

    public function bulkActiveQuestions($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $qDetail = $this->prepareBaseQuery($criteria);

        return DB::table('wg_customer_config_question_express')
            ->join(DB::raw("({$qDetail->toSql()}) as wg_config_question_express"), function ($join) {
                $join->on('wg_config_question_express.customer_id', '=', 'wg_customer_config_question_express.customer_id');
                $join->on('wg_config_question_express.customer_workplace_id', '=', 'wg_customer_config_question_express.customer_workplace_id');
                $join->on('wg_config_question_express.question_express_id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->mergeBindings($qDetail)
            ->update([
                "wg_customer_config_question_express.is_active" => 1,
                "wg_customer_config_question_express.updated_by" => $criteria->userId
            ]);
    }

    public function getHazardList($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $data = DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express_base"), function ($join) {
                $join->on('wg_config_classification_express_base.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->join("wg_config_classification_express", function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_classification_express_base.parent_id');
            })
            ->select(
                'wg_config_classification_express_base.parent_id AS id',
                'wg_config_classification_express_base.parent_name AS name',
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END) answers"),
                DB::raw("COUNT(*) questions"),
                DB::raw("(SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100 avg")
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId)
            ->where('wg_config_question_express.is_master', 0)
            ->groupBy(
                'wg_config_classification_express_base.parent_id',
                'wg_customer_config_question_express.customer_id',
                'wg_customer_config_question_express.customer_workplace_id'
            )
            ->orderBy('wg_config_classification_express.sort')
            ->get();

        for ($i = 0; $i < count($data); $i++) {
            if ($i == 0) {
                $data[$i]->isActive = true;
            } else {
                $data[$i]->isActive = $data[$i - 1]->answers == $data[$i - 1]->questions;
            }
        }

        return $data;
    }

    public function findHazard($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qHazardIntervention = DB::table('wg_customer_config_question_express_intervention')
            ->select(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 AND wg_customer_config_question_express_intervention.is_historical = 0 THEN 1 ELSE 0 END) AS closed'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical = 1 THEN 1 ELSE 0 END) AS historical')
            )
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id'
            );

        $data = DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->leftjoin(DB::raw("({$qHazardIntervention->toSql()}) as wg_customer_config_question_express_intervention"), function ($join) {
                $join->on('wg_customer_config_question_express_intervention.customer_id', '=', 'wg_customer_config_question_express.customer_id');
                $join->on('wg_customer_config_question_express_intervention.customer_question_express_id', '=', 'wg_customer_config_question_express.id');
            })
            ->mergeBindings($qHazardIntervention)
            ->select(
                'wg_config_classification_express.parent_id AS id',
                'wg_config_classification_express.parent_name AS name',

                'wg_config_classification_express.id AS childId',
                'wg_config_classification_express.name AS childName',
                'wg_config_classification_express.type',

                'wg_customer_config_question_express.customer_id AS customerId',
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                'wg_customer_config_question_express.rate',
                'wg_customer_config_question_express.id AS customerQuestionExpressId',

                'wg_config_question_express.id AS questionExpressId',
                'wg_config_question_express.description',
                'wg_config_question_express.priority',
                'wg_config_question_express.is_master',

                DB::raw('IF(wg_customer_config_question_express_intervention.opened > 0, 0, 1) AS canEdit')
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId)
            ->where('wg_config_classification_express.parent_id', $criteria->id)
            //->where('wg_config_classification_express.parent_id', 2)
            ->orderBy('wg_config_classification_express.sort')
            ->orderBy('wg_config_question_express.sort')
            ->get();

        $collection = new Collection($data);

        $hazards = $collection->groupBy('id');

        return $hazards->map(function ($items, $key) {
            $hazards = new Collection($items);
            $item = $hazards->first();
            $hazard = new \stdClass();
            $hazard->id = $item->id;
            $hazard->name = $item->name;
            $hazard->customerId = $item->customerId;
            $hazard->workplaceId = $item->workplaceId;
            $hazard->type = $item->type;


            $hazard->questionMasterList = $hazards->filter(function ($item) {
                return $item->is_master == 1 && $item->id == $item->childId;
            })->map(function ($item, $key) {
                $question = new \stdClass();
                $question->id = $item->customerQuestionExpressId;
                $question->questionExpressId = $item->questionExpressId;
                $question->description = $item->description;
                $question->priority = $item->priority;
                $question->rate = $item->rate;
                return $question;
            })->values();

            $masterQuestionCount = $hazard->questionMasterList->count();
            $masterQuestionYesCount = $hazard->questionMasterList->filter(function ($item) {
                return $item->rate == 'S';
            })->count();

            $hazard->showQuestions = $masterQuestionCount == 0 || ($masterQuestionCount == $masterQuestionYesCount);

            $hazard->questionList = $hazards->filter(function ($item) {
                return $item->is_master == 0 && $item->id == $item->childId;
            })->map(function ($item, $key) {
                $question = new \stdClass();
                $question->id = $item->customerQuestionExpressId;
                $question->questionExpressId = $item->questionExpressId;
                $question->description = $item->description;
                $question->priority = $item->priority;
                $question->rate = $item->rate;
                $question->canEdit = $item->is_master != 1 && $item->canEdit == 1;
                return $question;
            })->values();

            $children = $hazards->filter(function ($item) {
                return $item->type == 'S';
            })->groupBy('childId');

            $hazard->child = $children->map(function ($items, $key) {
                $children = new Collection($items);
                $item = $children->first();
                $hazard = new \stdClass();
                $hazard->id = $item->childId;
                $hazard->name = $item->childName;
                $hazard->customerId = $item->customerId;
                $hazard->workplaceId = $item->workplaceId;
                $hazard->type = $item->type;

                $hazard->questionMasterList = $children->filter(function ($item) {
                    return $item->is_master == 1 && $item->childId == $item->childId;
                })->map(function ($item, $key) {
                    $question = new \stdClass();
                    $question->id = $item->customerQuestionExpressId;
                    $question->questionExpressId = $item->questionExpressId;
                    $question->description = $item->description;
                    $question->priority = $item->priority;
                    $question->rate = $item->rate;
                    return $question;
                })->values();

                $masterQuestionCount = $hazard->questionMasterList->count();
                $masterQuestionYesCount = $hazard->questionMasterList->filter(function ($item) {
                    return $item->rate == 'S';
                })->count();

                $hazard->showQuestions = $masterQuestionCount == 0 || ($masterQuestionCount == $masterQuestionYesCount);

                $hazard->questionList = $children->filter(function ($item) {
                    return $item->is_master == 0 && $item->childId == $item->childId;
                })->map(function ($item, $key) {
                    $question = new \stdClass();
                    $question->id = $item->customerQuestionExpressId;
                    $question->questionExpressId = $item->questionExpressId;
                    $question->description = $item->description;
                    $question->priority = $item->priority;
                    $question->rate = $item->rate;
                    $question->canEdit = $item->is_master != 1 && $item->canEdit == 1;
                    return $question;
                })->values();

                return $hazard;
            })->values();

            return $hazard;
        })->first();

        //return $data;
    }

    public function findHazardIntervention($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $data = DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->select(
                'wg_config_classification_express.parent_id AS id',
                'wg_config_classification_express.parent_name AS name',

                'wg_config_classification_express.id AS childId',
                'wg_config_classification_express.name AS childName',
                'wg_config_classification_express.type',

                'wg_customer_config_question_express.customer_id AS customerId',
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                'wg_customer_config_question_express.rate',
                'wg_customer_config_question_express.id AS customerQuestionExpressId',

                'wg_config_question_express.id AS questionExpressId',
                'wg_config_question_express.description',
                'wg_config_question_express.priority',
                'wg_config_question_express.is_master'
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId)
            ->where('wg_config_classification_express.id', $criteria->id)
            ->where('wg_customer_config_question_express.rate', 'N')
            ->orderBy('wg_config_classification_express.sort')
            ->orderBy('wg_config_question_express.sort')
            ->get();

        $collection = new Collection($data);

        $hazards = $collection->groupBy('childId');

        return $hazards->map(function ($items, $key) {
            $hazards = new Collection($items);
            $item = $hazards->first();
            $hazard = new \stdClass();
            $hazard->id = $item->childId;
            $hazard->name = $item->childName;
            $hazard->customerId = $item->customerId;
            $hazard->workplaceId = $item->workplaceId;
            $hazard->type = $item->type;

            $hazard->questionList = $hazards->filter(function ($item) {
                return $item->is_master == 0 && $item->childId == $item->childId;
            })->map(function ($item, $key) {
                $question = new \stdClass();
                $question->id = $item->customerQuestionExpressId;
                $question->questionExpressId = $item->questionExpressId;
                $question->description = $item->description;
                $question->priority = $item->priority;
                $question->rate = $item->rate;
                $question->isActive = true;
                return $question;
            })->values();

            return $hazard;
        })->first();
    }

    public function findHazardInterventionHistorical($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qHazardIntervention = DB::table('wg_customer_config_question_express_intervention')
            ->select(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 THEN 1 ELSE 0 END) AS closed'),
                DB::raw('SUM(wg_customer_config_question_express_intervention.amount) AS amount')
            )
            ->where('wg_customer_config_question_express_intervention.is_historical', 1)
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id'
            );

        $data = DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->join(DB::raw("({$qHazardIntervention->toSql()}) as wg_customer_config_question_express_intervention"), function ($join) {
                $join->on('wg_customer_config_question_express_intervention.customer_question_express_id', '=', 'wg_customer_config_question_express.id');
            })
            ->mergeBindings($qHazardIntervention)
            ->select(
                'wg_config_classification_express.parent_id AS id',
                'wg_config_classification_express.parent_name AS name',

                'wg_config_classification_express.id AS childId',
                'wg_config_classification_express.name AS childName',
                'wg_config_classification_express.type',

                'wg_customer_config_question_express.customer_id AS customerId',
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                'wg_customer_config_question_express.rate',
                'wg_customer_config_question_express.id AS customerQuestionExpressId',

                'wg_config_question_express.id AS questionExpressId',
                'wg_config_question_express.description',
                'wg_config_question_express.priority',
                'wg_config_question_express.is_master'
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId)
            ->where('wg_config_classification_express.id', $criteria->id)
            ->orderBy('wg_config_classification_express.sort')
            ->orderBy('wg_config_question_express.sort')
            ->get();

        $collection = new Collection($data);

        $hazards = $collection->groupBy('childId');

        return $hazards->map(function ($items, $key) {
            $hazards = new Collection($items);
            $item = $hazards->first();
            $hazard = new \stdClass();
            $hazard->id = $item->childId;
            $hazard->name = $item->childName;
            $hazard->customerId = $item->customerId;
            $hazard->workplaceId = $item->workplaceId;
            $hazard->type = $item->type;

            $hazard->questionList = $hazards->filter(function ($item) {
                return $item->is_master == 0 && $item->childId == $item->childId;
            })->map(function ($item, $key) {
                $question = new \stdClass();
                $question->id = $item->customerQuestionExpressId;
                $question->questionExpressId = $item->questionExpressId;
                $question->description = $item->description;
                $question->priority = $item->priority;
                $question->rate = $item->rate;
                $question->isActive = true;
                return $question;
            })->values();

            return $hazard;
        })->first();
    }

    public function getWorkplaceStats($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        return DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->select(
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END) answers"),
                DB::raw("COUNT(*) questions"),
                DB::raw("(SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100 avg")
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId)
            ->where('wg_config_question_express.is_master', 0)
            ->groupBy(
                'wg_customer_config_question_express.customer_id',
                'wg_customer_config_question_express.customer_workplace_id'
            )
            ->first();
    }

    public function getHazardStats($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qHazardIntervention = DB::table('wg_customer_config_question_express_intervention')
            ->select(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 AND wg_customer_config_question_express_intervention.is_historical = 0 THEN 1 ELSE 0 END) AS closed'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical = 1 THEN 1 ELSE 0 END) AS historical'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical <> 1 THEN wg_customer_config_question_express_intervention.amount ELSE 0 END) AS amount'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical = 1 THEN wg_customer_config_question_express_intervention.amount ELSE 0 END) AS amountHistorical')
            )
            //->where('wg_customer_config_question_express_intervention.is_historical', 0)
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id'
            );

        if (isset($criteria->year)) {
            $qHazardIntervention->whereYear('wg_customer_config_question_express_intervention.execution_date', '=', $criteria->year);
            $qHazardIntervention->groupBy(DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date)'));
        }

        return DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->leftjoin(DB::raw("({$qHazardIntervention->toSql()}) as wg_customer_config_question_express_intervention"), function ($join) {
                $join->on('wg_customer_config_question_express_intervention.customer_question_express_id', '=', 'wg_customer_config_question_express.id');
            })
            ->mergeBindings($qHazardIntervention)
            ->select(
                'wg_config_classification_express.id',
                'wg_config_classification_express.name',
                'wg_customer_config_question_express.customer_id AS customerId',
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                DB::raw("SUM(CASE WHEN wg_config_question_express.priority = 'Alta' AND wg_customer_config_question_express.rate = 'N' THEN 1 ELSE 0 END) AS highPriority"),
                DB::raw("SUM(CASE WHEN wg_config_question_express.priority = 'Media' AND wg_customer_config_question_express.rate = 'N' THEN 1 ELSE 0 END) AS mediumPriority"),
                DB::raw("SUM(CASE WHEN wg_config_question_express.priority = 'Baja' AND wg_customer_config_question_express.rate = 'N' THEN 1 ELSE 0 END) AS lowPriority"),
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate = 'N' THEN 1 ELSE 0 END) AS quantity"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.opened, 0)) AS opened"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.closed, 0)) AS closed"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.historical, 0)) AS historical"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.amount, 0)) AS amount"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.amountHistorical, 0)) AS amountHistorical")
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId)
            ->where('wg_config_classification_express.parent_id', $criteria->id)
            ->where('wg_config_question_express.is_master', 0)
            //->where('wg_customer_config_question_express.rate', 'N')
            ->groupBy(
                'wg_customer_config_question_express.customer_id',
                'wg_customer_config_question_express.customer_workplace_id',
                'wg_config_classification_express.id'
            )
            ->orderBy('wg_config_classification_express.sort')
            ->orderBy('wg_config_classification_express.id')
            ->get();
    }

    public function getHazardGeneralStats($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qHazardIntervention = DB::table('wg_customer_config_question_express_intervention')
            ->select(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 AND wg_customer_config_question_express_intervention.is_historical = 0 THEN 1 ELSE 0 END) AS closed'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical = 1 THEN 1 ELSE 0 END) AS historical'),
                //DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical <> 1 THEN wg_customer_config_question_express_intervention.amount ELSE 0 END) AS amount'),
                DB::raw('SUM(wg_customer_config_question_express_intervention.amount) AS amount'),
                DB::raw('COUNT(*) AS qty')
            )
            /*->select(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 THEN 1 ELSE 0 END) AS closed'),
                DB::raw('SUM(wg_customer_config_question_express_intervention.amount) AS amount'),
                DB::raw('COUNT(*) AS qty')
            )
            ->where('wg_customer_config_question_express_intervention.is_historical', 0)*/
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id'
            );


        if (isset($criteria->year)) {
            $qHazardIntervention->whereYear('wg_customer_config_question_express_intervention.execution_date', '=', $criteria->year);
            $qHazardIntervention->groupBy(DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date)'));
        }

        $query = DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->leftjoin(DB::raw("({$qHazardIntervention->toSql()}) as wg_customer_config_question_express_intervention"), function ($join) {
                $join->on('wg_customer_config_question_express_intervention.customer_question_express_id', '=', 'wg_customer_config_question_express.id');
            })
            ->mergeBindings($qHazardIntervention)
            ->select(
                'wg_config_classification_express.id',
                'wg_config_classification_express.name',
                'wg_customer_config_question_express.customer_id AS customerId',
                'wg_customer_config_question_express.customer_workplace_id AS workplaceId',
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate = 'N' AND `wg_config_question_express`.`priority` = 'Alta' THEN 1 ELSE 0 END) AS highPriority"),
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate = 'N' AND  `wg_config_question_express`.`priority` = 'Media' THEN 1 ELSE 0 END) AS mediumPriority"),
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate = 'N' AND  `wg_config_question_express`.`priority` = 'Baja' THEN 1 ELSE 0 END) AS lowPriority"),
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate = 'N' THEN 1 ELSE 0 END) AS quantity"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.opened, 0)) AS opened"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.closed, 0)) AS closed"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.historical, 0)) AS historical"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.amount, 0)) AS amount"),
                DB::raw("(SUM(IFNULL(wg_customer_config_question_express_intervention.opened, 0)) * 100) / SUM(IFNULL(wg_customer_config_question_express_intervention.qty, 0)) AS openedAvg"),
                DB::raw("(SUM(IFNULL(wg_customer_config_question_express_intervention.closed, 0)) * 100) / SUM(IFNULL(wg_customer_config_question_express_intervention.qty, 0)) AS closedAvg"),
                DB::raw("(SUM(IFNULL(wg_customer_config_question_express_intervention.historical, 0)) * 100) / SUM(IFNULL(wg_customer_config_question_express_intervention.qty, 0)) AS historicalAvg")
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_config_question_express.is_master', 0)
            //->where('wg_customer_config_question_express.rate', 'N')
            ->groupBy('wg_customer_config_question_express.customer_id')
            ->orderBy('wg_config_classification_express.sort');

        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
            $query->groupBy('wg_customer_config_question_express.customer_workplace_id');
        }

        $data = $query->first();

        $data->highPriorityAvg = ($data->highPriority * 100) / $data->quantity;
        $data->mediumPriorityAvg = ($data->mediumPriority * 100) / $data->quantity;
        $data->lowPriorityAvg = ($data->lowPriority * 100) / $data->quantity;
        $data->openedAvg = round($data->openedAvg, 2);

        return $data;
    }

    public function getChartPieHazardInterventionStats($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qHazardIntervention = DB::table('wg_customer_config_question_express_intervention')
            ->select(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 AND wg_customer_config_question_express_intervention.is_historical = 0 THEN 1 ELSE 0 END) AS closed'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_historical = 1 THEN 1 ELSE 0 END) AS historical')
            )
            //->where('wg_customer_config_question_express_intervention.is_historical', 0)
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id'
            );

        if (isset($criteria->year)) {
            $qHazardIntervention->whereYear('wg_customer_config_question_express_intervention.execution_date', '=', $criteria->year);
            $qHazardIntervention->groupBy(DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date)'));
        }

        $query = DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->leftjoin(DB::raw("({$qHazardIntervention->toSql()}) as wg_customer_config_question_express_intervention"), function ($join) {
                $join->on('wg_customer_config_question_express_intervention.customer_question_express_id', '=', 'wg_customer_config_question_express.id');
            })
            ->mergeBindings($qHazardIntervention)
            ->select(
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.opened, 0)) AS opened"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.closed, 0)) AS closed"),
                DB::raw("SUM(IFNULL(wg_customer_config_question_express_intervention.historical, 0)) AS historical")
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_config_question_express.is_master', 0)
            //->where('wg_customer_config_question_express.rate', 'N')
            ->groupBy('wg_customer_config_question_express.customer_id')
            ->orderBy('wg_config_classification_express.sort');

        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
            $query->groupBy('wg_customer_config_question_express.customer_workplace_id');
        }

        $data = $query->first();

        $chart = [
            [
                "label" => "{$data->opened} Abiertos",
                "value" => $data->opened,
                "color" => '#68bc47'
            ],
            [
                "label" => "{$data->closed} Cerrados",
                "value" => $data->closed,
                "color" => '#6f8896'
            ],
            [
                "label" => "{$data->historical} Históricos",
                "value" => $data->historical,
                "color" => '#64d8cb'
            ],
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }

    public function getWorkplaceList($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        return DB::table('wg_customer_config_question_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->join("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_question_express.customer_workplace_id');
            })
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END) answers"),
                DB::raw("COUNT(*) questions"),
                DB::raw("(SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100 avg")
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_config_question_express.is_master', 0)
            ->groupBy(
                'wg_customer_config_question_express.customer_id',
                'wg_customer_config_question_express.customer_workplace_id'
            )
            ->havingRaw("COUNT(*) = SUM(CASE WHEN wg_customer_config_question_express.rate IS NOT NULL THEN 1 ELSE 0 END)")
            ->get()
            ->toArray();
    }

    private function prepareBaseQuery($criteria)
    {
        return DB::table('wg_config_classification_express')
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.classification_express_id', '=', 'wg_config_classification_express.id');
            })
            ->join("wg_customer_config_workplace", function ($join) use ($criteria) {
                $join->where('wg_customer_config_workplace.id', '=', $criteria->workplaceId);
            })
            ->join("wg_customers", function ($join) use ($criteria) {
                $join->where('wg_customers.id', '=', $criteria->customerId);
            })
            ->join("users", function ($join) use ($criteria) {
                $join->where('users.id', '=', $criteria->userId);
            })
            ->select(
                DB::raw("wg_customers.id AS customer_id"),
                "wg_customer_config_workplace.id AS customer_workplace_id",
                "wg_config_question_express.id AS question_express_id"
            )
            ->where('wg_config_question_express.is_active', 1)
            ->where('wg_config_classification_express.is_active', 1);
    }

    private function prepareUnionHazardQuery()
    {
        $q1 = DB::table('wg_config_classification_express')
            ->select(
                'wg_config_classification_express.id',
                'wg_config_classification_express.name',
                'wg_config_classification_express.id AS parent_id',
                'wg_config_classification_express.name AS parent_name',
                'wg_config_classification_express.sort',
                'wg_config_classification_express.type'
            )
            ->where('type', 'F')
            ->where('is_active', 1);

        $q2 = DB::table('wg_config_classification_express')
            ->join(DB::raw('wg_config_classification_express AS wg_config_classification_express_child'), function ($join) {
                $join->on('wg_config_classification_express_child.parent_id', '=', 'wg_config_classification_express.id');
            })
            ->select(
                'wg_config_classification_express_child.id',
                'wg_config_classification_express_child.name',
                'wg_config_classification_express.id AS parent_id',
                'wg_config_classification_express.name AS parent_name',
                'wg_config_classification_express_child.sort',
                'wg_config_classification_express_child.type'
            )
            ->where('wg_config_classification_express.type', 'F')
            ->where('wg_config_classification_express.is_active', 1)
            ->where('wg_config_classification_express_child.is_active', 1);

        return $q1->union($q2)->mergeBindings($q2);
    }
}
