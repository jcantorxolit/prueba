<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItem40595;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

use AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595\CustomerRoadSafetyItemComment40595Repository;
use AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemVerification40595\CustomerRoadSafetyItemVerification40595Repository;
use AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDetail40595\CustomerRoadSafetyItemDetail40595Repository;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\CustomerRoadSafetyTracking40595Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRoadSafetyItem40595Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerRoadSafetyItem40595Model());

        $this->service = new CustomerRoadSafetyItem40595Service();
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

        $qDetail = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerRoadSafetyId") {
                        $qDetail->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_40595.customer_road_safety_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }
        $customerRoadSafetyIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerRoadSafetyId");

        $qSub = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            // ->join("wg_road_safety_item_criterion_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            // })
            // ->join("wg_customers", function ($join) {
            //     $join->on('wg_customers.totalEmployee', '=', 'wg_road_safety_item_criterion_40595.size');
            // })
            ->join("wg_customer_road_safety_40595", function ($join) use ($customerRoadSafetyIdField) {
                $join->where('wg_customer_road_safety_40595.id', '=', $customerRoadSafetyIdField->value);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_road_safety_40595.id', '=', 'detail.road_safety_item_id');
            })
            ->select(
                'wg_road_safety_40595.id',
                'wg_road_safety_cycle_40595.description',
                'wg_road_safety_40595.parent_id',
                'wg_road_safety_cycle_40595.id AS cycle_id',
                DB::raw('COUNT(*) items'),
                DB::raw('SUM(CASE WHEN ISNULL(detail.id) THEN 0 ELSE 1 END) AS checked'),
                DB::raw('SUM(CASE WHEN detail.`code` = \'cp\' OR detail.`code` = \'nac\' THEN wg_road_safety_item_40595.`value` ELSE 0 END) AS total')
            )
            ->mergeBindings($qDetail)
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->groupBy('wg_road_safety_cycle_40595.description', 'wg_road_safety_cycle_40595.id');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerId") {
                        //$qSub->where(SqlHelper::getPreparedField('wg_customers.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
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
                        } catch (Exception $ex) {
                        }
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

        $qDetail = DB::table('wg_customer_road_safety_item_40595')
            ->leftjoin("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.is_freezed', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerRoadSafetyId") {
                        $qDetail->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_40595.customer_road_safety_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('wg_road_safety_item_40595.id', '=', 'detail.road_safety_item_id');
            })
            ->select(
                'wg_road_safety_40595.id',
                'wg_road_safety_40595.description',
                'wg_road_safety_40595.parent_id',
                'wg_road_safety_cycle_40595.id AS cycle_id',
                DB::raw('COUNT(*) items'),
                DB::raw('SUM(CASE WHEN ISNULL(detail.code) THEN 0 ELSE 1 END) AS checked'),
                DB::raw('SUM(CASE WHEN detail.`code` = \'cp\' OR detail.`code` = \'nac\' THEN wg_road_safety_item_40595.`value` ELSE 0 END) AS total')
            )
            ->mergeBindings($qDetail)
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->groupBy('wg_road_safety_40595.description', 'wg_road_safety_40595.id');


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
                        } catch (Exception $ex) {
                        }
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
            "id" => "wg_customer_road_safety_item_40595.id",
            "description" => "wg_road_safety_40595.description",
            "numeral" => "wg_road_safety_40595.numeral",
            "value" => "wg_road_safety_item_40595.value",
            //"criterion" => "wg_road_safety_item_criterion_40595.description AS criterion",
            "rateId" => "wg_customer_road_safety_item_40595.rate_id",
            "rateCode" => "wg_road_safety_rate_40595.code as rate_code",
            "rateText" => "wg_road_safety_rate_40595.text as rate_text",
            "customerRoadSafetyId" => "wg_customer_road_safety_item_40595.customer_road_safety_id",
            "itemId" => "wg_customer_road_safety_item_40595.road_safety_item_id",
            "roadSafetyId" => "wg_road_safety_40595.id AS road_safety_id",
            "cycleId" => "wg_road_safety_cycle_40595.id AS cycle_id",
            "cycleDescription" => "wg_road_safety_cycle_40595.description AS cycle_description",
            //"criterionId" => "wg_road_safety_item_criterion_40595.id AS criterion_id",
            //"customerId" => "wg_customers.id AS customer_id",
            "comment" => 'wg_customer_road_safety_item_comment_40595.comment'
            //"period" => 'wg_customer_road_safety_40595.period',
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('numeral');
        }

        $query = $this->query();

        $qDetail = DB::table('wg_customer_road_safety_item_comment_40595')
            ->select(
                DB::raw("MAX(wg_customer_road_safety_item_comment_40595.id) AS id"),
                'wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id'
            )
            ->join('wg_customer_road_safety_item_40595', function ($join) {
                $join->on('wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.id');
            })
            ->join('wg_customer_road_safety_40595', function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->where('wg_customer_road_safety_item_comment_40595.type', 'A')
            ->groupBy('wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id');

        $customerRoadSafetyIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerRoadSafetyId");

        if ($customerRoadSafetyIdField) {
            $qDetail->where('wg_customer_road_safety_40595.id', $customerRoadSafetyIdField->value);
        }

        /* Example relation*/
        $query->join('wg_customer_road_safety_40595', function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->join('wg_road_safety_40595', function ($join) {
            $join->on('wg_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
        })->join('wg_road_safety_item_40595', function ($join) {
            $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
        })->join('wg_road_safety_cycle_40595', function ($join) {
            $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_road_safety_40595.cycle_id');
        })->leftjoin('wg_road_safety_rate_40595', function ($join) {
            $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('detail.customer_road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.id');
        })->leftjoin('wg_customer_road_safety_item_comment_40595', function ($join) {
            $join->on('wg_customer_road_safety_item_comment_40595.id', '=', 'detail.id');
        })->mergeBindings($qDetail);

        $query->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo');

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
                        } catch (Exception $ex) {
                        }
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
            "id" => "wg_customer_road_safety_item_40595.id",
            "description" => "wg_road_safety_item_40595.description",
            "numeral" => "wg_road_safety_item_40595.numeral",
            //"criterion" => "wg_road_safety_item_criterion_40595.description AS criterion",
            "rateId" => "wg_customer_road_safety_item_40595.rate_id",
            "rateCode" => "wg_road_safety_rate_40595.code as rate_code",
            "rateText" => "wg_road_safety_rate_40595.text as rate_text",
            "customerRoadSafetyId" => "wg_customer_road_safety_item_40595.customer_road_safety_id",
            "itemId" => "wg_customer_road_safety_item_40595.road_safety_item_id",
            "roadSafetyId" => "wg_road_safety_40595.id AS road_safety_id",
            "cycleId" => "wg_road_safety_cycle_40595.id AS cycle_id",
            //"criterionId" => "wg_road_safety_item_criterion_40595.id AS criterion_id",
            "customerId" => "wg_customer_road_safety_40595.customer_id",
            "comment" => 'wg_customer_road_safety_item_comment_40595.comment',
            "period" => 'wg_customer_road_safety_40595.period',
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('numeral');
        }

        $query = $this->query();

        $qDetail = DB::table('wg_customer_road_safety_item_comment_40595')
            ->select(
                DB::raw("MAX(wg_customer_road_safety_item_comment_40595.id) AS id"),
                'wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id'
            )
            ->join('wg_customer_road_safety_item_40595', function ($join) {
                $join->on('wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.id');
            })
            ->join('wg_customer_road_safety_40595', function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->where('wg_customer_road_safety_item_comment_40595.type', 'A')
            ->groupBy('wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id');

        $customerRoadSafetyIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerRoadSafetyId");

        if ($customerRoadSafetyIdField) {
            $qDetail->where('wg_customer_road_safety_40595.id', $customerRoadSafetyIdField->value);
        }

        /* Example relation*/
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
            })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
                $join->on('detail.customer_road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.id');
            })->leftjoin('wg_customer_road_safety_item_comment_40595', function ($join) {
                $join->on('wg_customer_road_safety_item_comment_40595.id', '=', 'detail.id');
            })->mergeBindings($qDetail);

        $query->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customer_road_safety_item_40595.is_freezed', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo');

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
                        } catch (Exception $ex) {
                        }
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

        $entityModel->customerRoadSafetyId = $entity->customerRoadSafetyId;
        $entityModel->roadSafetyItemId = $entity->roadSafetyItemId;

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
            CustomerRoadSafetyItemComment40595Repository::create($entity);
        }

        (new CustomerRoadSafetyItemVerification40595Repository)->bulkInsertOrUpdate($entity->verificationModeList, $entityModel->id);


        $result = $this->parseModelWithRelations($entityModel);


        //TODO
        CustomerRoadSafetyTracking40595Repository::createMissingMonthlyReport($result->customerRoadSafetyId);
        CustomerRoadSafetyTracking40595Repository::insertMonthlyReport($result->customerRoadSafetyId, null);

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
        return  DB::table('wg_customer_road_safety_40595')->where('id', $id)->first();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            //$repository = new CustomerRoadSafety40595Repository();

            $entity->id = $model->id;
            $entity->customerRoadSafetyId = $model->customerRoadSafetyId;
            $entity->customerRoadSafety = $this->findCustomerRoadSafety($model->customerRoadSafetyId);
            $entity->roadSafetyItemId = $model->roadSafetyItemId;
            //$entity->roadSafetyItem = $this->findCustomerRoadSafety($model->roadSafetyItemId);
            $entity->rateId = $model->rateId;

            $entity->rate = $model->getRate();
            $entity->realRate = null;

            if ($entity->rate != null && ($entity->rate->id == 1 || $entity->rate->id == 2)) {
                $entity->realRate = $entity->rate;
                $entity->rate = null;
            } else {
                $entity->rate = $entity->rate != '' ? $entity->rate : null;
            }

            $entity->verificationModeList = $this->service->getVerificationList($entity);
            //$entity->questionList = $this->service->getQuestion($entity);
            $entity->lastComment = $this->service->getLastComment($entity);
            $entity->comment = $entity->lastComment ? $entity->lastComment->comment : null;

            return $entity;
        } else {
            return null;
        }
    }

    private function findCustomerRoadSafety($id)
    {
        return DB::table('wg_customer_road_safety_40595')->where('id', $id)->first();
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
        $entity = $this->findMinimumStandard($criteria->customerRoadSafetyId);

        if ($entity == null || $entity->status == 'A') {
            $data = $this->service->getExportData($criteria);
        } else {
            $data = $this->service->getExportDataClosed($criteria);
        }
        $filename = 'PESV_40595_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'PESV 40595', $data);
    }
}
