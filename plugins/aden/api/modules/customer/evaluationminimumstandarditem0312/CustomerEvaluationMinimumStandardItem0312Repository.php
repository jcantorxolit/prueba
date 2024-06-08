<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

use AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\CustomerEvaluationMinimumStandard0312Repository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemComment0312\CustomerEvaluationMinimumStandardItemComment0312Repository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemVerification0312\CustomerEvaluationMinimumStandardItemVerification0312Repository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardItemDetail0312\CustomerEvaluationMinimumStandardItemDetail0312Repository;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\CustomerEvaluationMinimumStandardTracking0312Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerEvaluationMinimumStandardItem0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerEvaluationMinimumStandardItem0312Model());

        $this->service = new CustomerEvaluationMinimumStandardItem0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "category.id",
            "description" => "category.description",
            "items" => DB::raw("SUM(items) AS items"),
            "checked" => DB::raw("SUM(checked) AS checked"),
            "advance" => DB::raw("ROUND(IFNULL(SUM((checked / items) * 100), 0),2) AS advance"),
            "average" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS average"),
            "total" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS total"),
            "cycleId" => "category.cycle_id",
            "parentId" => "category.parent_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerEvaluationMinimumStandardId") {
                        $qDetail->where(SqlHelper::getPreparedField('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_minimum_standard_item_0312.id', '=', 'detail.minimum_standard_item_id');
            })
            ->select(
                'wg_minimum_standard_0312.id',
                'wg_minimum_standard_0312.description',
                'wg_minimum_standard_0312.parent_id',
                'wg_config_minimum_standard_cycle_0312.id AS cycle_id',
                DB::raw('COUNT(*) items'),
                DB::raw('SUM(CASE WHEN ISNULL(detail.id) THEN 0 ELSE 1 END) AS checked'),
                DB::raw('SUM(CASE WHEN detail.`code` = \'cp\' OR detail.`code` = \'nac\' THEN wg_minimum_standard_item_0312.`value` ELSE 0 END) AS total')
            )
            ->mergeBindings($qDetail)
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->groupBy('wg_minimum_standard_0312.description', 'wg_minimum_standard_0312.id');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerId") {
                        $qSub->where(SqlHelper::getPreparedField('wg_customers.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as category")));

        $query->groupBy('category.id')->mergeBindings($qSub);


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'cycleId') {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) { }
                    }
                });
            }
        }

        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns)->total() : (is_array($data = $query->get($this->columns)) ? count($data) : $data->count());
        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->take($criteria->take)->skip($criteria->skip)->get($this->columns) : $query->get($this->columns));

        return $result;
    }

    public function allClosed($criteria)
    {
        $this->setColumns([
            "id" => "category.id",
            "description" => "category.description",
            "items" => DB::raw("SUM(items) AS items"),
            "checked" => DB::raw("SUM(checked) AS checked"),
            "advance" => DB::raw("ROUND(IFNULL(SUM((checked / items) * 100), 0),2) AS advance"),
            "average" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS average"),
            "total" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS total"),
            "cycleId" => "category.cycle_id",
            "parentId" => "category.parent_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->leftjoin("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerEvaluationMinimumStandardId") {
                        $qDetail->where(SqlHelper::getPreparedField('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_minimum_standard_item_0312.id', '=', 'detail.minimum_standard_item_id');
            })
            ->select(
                'wg_minimum_standard_0312.id',
                'wg_minimum_standard_0312.description',
                'wg_minimum_standard_0312.parent_id',
                'wg_config_minimum_standard_cycle_0312.id AS cycle_id',
                DB::raw('COUNT(*) items'),
                DB::raw('SUM(CASE WHEN ISNULL(detail.code) THEN 0 ELSE 1 END) AS checked'),
                DB::raw('SUM(CASE WHEN detail.`code` = \'cp\' OR detail.`code` = \'nac\' THEN wg_minimum_standard_item_0312.`value` ELSE 0 END) AS total')
            )
            ->mergeBindings($qDetail)
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->groupBy('wg_minimum_standard_0312.description', 'wg_minimum_standard_0312.id');


        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as category")));

        $query->groupBy('category.id')->mergeBindings($qSub);


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'cycleId') {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) { }
                    }
                });
            }
        }

        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns)->total() : (is_array($data = $query->get($this->columns)) ? count($data) : $data->count());
        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->take($criteria->take)->skip($criteria->skip)->get($this->columns) : $query->get($this->columns));

        return $result;
    }

    public function allQuestion($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_evaluation_minimum_standard_item_0312.id",
            "description" => "wg_minimum_standard_item_0312.description",
            "numeral" => "wg_minimum_standard_item_0312.numeral",
            "criterion" => "wg_minimum_standard_item_criterion_0312.description AS criterion",
            "rateId" => "wg_customer_evaluation_minimum_standard_item_0312.rate_id",
            "rateCode" => "wg_config_minimum_standard_rate_0312.code as rate_code",
            "rateText" => "wg_config_minimum_standard_rate_0312.text as rate_text",
            "customerEvaluationMinimumStandardId" => "wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id",
            "itemId" => "wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id",
            "minimumStandardId" => "wg_minimum_standard_0312.id AS minimum_standard_id",
            "cycleId" => "wg_config_minimum_standard_cycle_0312.id AS cycle_id",
            "criterionId" => "wg_minimum_standard_item_criterion_0312.id AS criterion_id",
            "customerId" => "wg_customers.id AS customer_id",
            "comment" => 'wg_customer_evaluation_minimum_standard_item_comment_0312.comment',
            "period" => 'wg_customer_evaluation_minimum_standard_0312.period',
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $query = $this->query();

        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_comment_0312')
            ->select(
                DB::raw("MAX(wg_customer_evaluation_minimum_standard_item_comment_0312.id) AS id"),
                'wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id'
            )
            ->join('wg_customer_evaluation_minimum_standard_item_0312', function($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.id');
            })
            ->join('wg_customer_evaluation_minimum_standard_0312', function($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
            })
            ->where('wg_customer_evaluation_minimum_standard_item_comment_0312.type', 'A')
            ->groupBy('wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id');

        $customerEvaluationMinimumStandardIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerEvaluationMinimumStandardId");

        if ($customerEvaluationMinimumStandardIdField) {
            $qDetail->where('wg_customer_evaluation_minimum_standard_0312.id', $customerEvaluationMinimumStandardIdField->value);
        }

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
        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('detail.customer_evaluation_minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.id');

        })->leftjoin('wg_customer_evaluation_minimum_standard_item_comment_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_item_comment_0312.id', '=', 'detail.id');

        })->mergeBindings($qDetail);

        $query->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) { }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : (is_array($data = $query->get()) ? count($data) : $data->count());


        return $result;
    }

    public function allQuestionClosed($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_evaluation_minimum_standard_item_0312.id",
            "description" => "wg_minimum_standard_item_0312.description",
            "numeral" => "wg_minimum_standard_item_0312.numeral",
            //"criterion" => "wg_minimum_standard_item_criterion_0312.description AS criterion",
            "rateId" => "wg_customer_evaluation_minimum_standard_item_0312.rate_id",
            "rateCode" => "wg_config_minimum_standard_rate_0312.code as rate_code",
            "rateText" => "wg_config_minimum_standard_rate_0312.text as rate_text",
            "customerEvaluationMinimumStandardId" => "wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id",
            "itemId" => "wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id",
            "minimumStandardId" => "wg_minimum_standard_0312.id AS minimum_standard_id",
            "cycleId" => "wg_config_minimum_standard_cycle_0312.id AS cycle_id",
            //"criterionId" => "wg_minimum_standard_item_criterion_0312.id AS criterion_id",
            "customerId" => "wg_customer_evaluation_minimum_standard_0312.customer_id",
            "comment" => 'wg_customer_evaluation_minimum_standard_item_comment_0312.comment',
            "period" => 'wg_customer_evaluation_minimum_standard_0312.period',
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $query = $this->query();

        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_comment_0312')
            ->select(
                DB::raw("MAX(wg_customer_evaluation_minimum_standard_item_comment_0312.id) AS id"),
                'wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id'
            )
            ->join('wg_customer_evaluation_minimum_standard_item_0312', function($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.id');
            })
            ->join('wg_customer_evaluation_minimum_standard_0312', function($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
            })
            ->where('wg_customer_evaluation_minimum_standard_item_comment_0312.type', 'A')
            ->groupBy('wg_customer_evaluation_minimum_standard_item_comment_0312.customer_evaluation_minimum_standard_item_id');

        $customerEvaluationMinimumStandardIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerEvaluationMinimumStandardId");

        if ($customerEvaluationMinimumStandardIdField) {
            $qDetail->where('wg_customer_evaluation_minimum_standard_0312.id', $customerEvaluationMinimumStandardIdField->value);
        }

        /* Example relation*/
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
        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('detail.customer_evaluation_minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.id');

        })->leftjoin('wg_customer_evaluation_minimum_standard_item_comment_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_item_comment_0312.id', '=', 'detail.id');

        })->mergeBindings($qDetail);

        $query->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) { }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : (is_array($data = $query->get()) ? count($data) : $data->count());


        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerEvaluationMinimumStandardId = $entity->customerEvaluationMinimumStandardId;
        $entityModel->minimumStandardItemId = $entity->minimumStandardItemId;

        if ($entity->realRate != null) {
            $entityModel->rateId = $entity->realRate->id;
        } else {
            $entityModel->rateId = $entity->rate->id;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if ($entity->lastComment == null || $entity->lastComment->comment != $entity->comment) {
            CustomerEvaluationMinimumStandardItemComment0312Repository::create($entity);
        }

        (new CustomerEvaluationMinimumStandardItemVerification0312Repository)->bulkInsertOrUpdate($entity->verificationModeList, $entityModel->id);

        (new CustomerEvaluationMinimumStandardItemDetail0312Repository)->bulkInsertOrUpdate($entity->legalFrameworkList, $entityModel->id);


        $result = $this->parseModelWithRelations($entityModel);

        CustomerEvaluationMinimumStandardTracking0312Repository::createMissingMonthlyReport($result->customerEvaluationMinimumStandardId);
        CustomerEvaluationMinimumStandardTracking0312Repository::insertMonthlyReport($result->customerEvaluationMinimumStandardId, $result->customerEvaluationMinimumStandard->customerId);
        //CustomerEvaluationMinimumStandardTracking0312Repository::updateMonthlyReport($result->customerEvaluationMinimumStandardId, $result->customerEvaluationMinimumStandard->customerId);

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function findMinimumStandard($id)
    {
        return  DB::table('wg_customer_evaluation_minimum_standard_0312')->where('id', $id)->first();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $repository = new CustomerEvaluationMinimumStandard0312Repository();

            $entity->id = $model->id;
            $entity->customerEvaluationMinimumStandardId = $model->customerEvaluationMinimumStandardId;
            $entity->customerEvaluationMinimumStandard = $repository->find($model->customerEvaluationMinimumStandardId);
            $entity->minimumStandardItemId = $model->minimumStandardItemId;
            $entity->rateId = $model->rateId;

            $entity->rate = $model->getRate();
            $entity->realRate = null;

            if ($entity->rate != null && ($entity->rate->id == 1 || $entity->rate->id == 2)) {
                $entity->realRate = $entity->rate;
                $entity->rate = null;
            } else {
                $entity->rate = $entity->rate != '' ? $entity->rate : null;
            }

            $entity->criterion = $this->service->getCriterion($entity);
            $entity->legalFrameworkList = $this->service->getLegalFramework($entity);
            $entity->verificationModeList = $this->service->getVerificationMode($entity);
            $entity->questionList = $this->service->getQuestion($entity);
            $entity->lastComment = $this->service->getLastComment($entity);
            $entity->comment = $entity->lastComment ? $entity->lastComment->comment : null;

            return $entity;
        } else {
            return null;
        }
    }

    protected function parseModel($data)
    {
        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator) {
            $models = $data->all();
        } else {
            $models = $data;
        }

        $modelClass = get_class($this->model);

        if (is_array($models) || $models instanceof Collection || $models instanceof \October\Rain\Support\Collection) {
            $parsed = array();
            foreach ($models as $model) {

                if (isset($model->rateId) && isset($model->rateCode) && isset($model->rateText)) {
                    $model->rate = ['id' => $model->rateId, 'code' => $model->rateCode, 'text' => $model->rateText];
                }

                if ($model instanceof $modelClass) {
                    $parsed[] = $model;
                } else {
                    $parsed[] = $model;
                }
            }

            return $parsed;
        } else if ($data instanceof $modelClass) {
            return $data;
        } else {
            return null;
        }
    }

    public function exportExcel($criteria)
    {
        $entity = $this->findMinimumStandard($criteria->customerEvaluationMinimumStandardId);

        if ($entity == null || $entity->status == 'A') {
            $data = $this->service->getExportData($criteria);
        } else {
            $data = $this->service->getExportDataClosed($criteria);
        }
        $filename = 'AutoEvaluacion_EM_0312_'. Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'EM 0312', $data);
    }
}
