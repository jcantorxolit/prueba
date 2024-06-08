<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ContractSafetyInspectionListItem;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\Customer\CustomerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Wgroup\SystemParameter\SystemParameter;

class CustomerContractSafetyInspectionListItemRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerContractSafetyInspectionListItemModel());

        $this->service = new CustomerContractSafetyInspectionListItemService();
    }

    public static function getCustomFilters()
    {
        return [];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "category.id",
            "description" => "category.description",
            "questions" => DB::raw("SUM(questions) AS questions"),
            "answers" => DB::raw("SUM(answers) AS answers"),
            "advance" => DB::raw("ROUND(IFNULL(SUM((answers / questions) * 100), 0),2) AS advance"),
            "average" => DB::raw("ROUND(IFNULL(SUM(total / questions), 0),2) AS average"),
            "total" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS total"),
            "customerSafetyInspectionConfigListId" => "category.customerSafetyInspectionConfigListId",
            "customerContractorId" => "category.customerContractorId",
            "period" => "category.period",
            "sort" => "category.sort"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('sort');
        }

        $qDetail = DB::table('wg_customer_safety_inspection');
        $qDetail->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_safety_inspection.contractorType', '=', 'wg_customer_contractor.contractor_type_id');

        })->join('wg_customer_contractor_safety_inspection', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');
            $join->on('wg_customer_contractor_safety_inspection.customer_id', '=', 'wg_customer_contractor.contractor_id');

        })->join('wg_customer_safety_inspection_list', function ($join) {
            $join->on('wg_customer_safety_inspection_list.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');

        })->join('wg_customer_safety_inspection_config_list', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list.id', '=', 'wg_customer_safety_inspection_list.customer_safety_inspection_config_list_id');

        })->join('wg_customer_safety_inspection_config_list_group', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');

        })->join('wg_customer_safety_inspection_config_list_item', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_item.customer_safety_inspection_config_list_group_id', '=', 'wg_customer_safety_inspection_config_list_group.id');

        })->join('wg_customer_contractor_safety_inspection_list_item', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id', '=', 'wg_customer_safety_inspection_list.id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id', '=', 'wg_customer_safety_inspection_config_list_item.id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_contractor_id', '=', 'wg_customer_contractor.id');

        })->leftjoin(DB::raw('wg_customer_safety_inspection_config_list_validation AS dangerousness'), function ($join) {
            $join->on('dangerousness.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('dangerousness.id', '=', 'wg_customer_contractor_safety_inspection_list_item.dangerousnessValue');

        })->leftjoin(DB::raw('wg_customer_safety_inspection_config_list_validation AS priority'), function ($join) {
            $join->on('priority.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('priority.id', '=', 'wg_customer_contractor_safety_inspection_list_item.priorityValue');

        })->leftjoin(DB::raw('wg_customer_safety_inspection_config_list_validation AS existingControl'), function ($join) {
            $join->on('existingControl.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('existingControl.id', '=', 'wg_customer_contractor_safety_inspection_list_item.existingControlValue');

        })->select(
            'wg_customer_contractor_safety_inspection_list_item.*',
            DB::raw('IFNULL(dangerousness.`value`,0) AS dangerousness'),
            DB::raw('IFNULL(priority.`value`,0) AS priority'),
            DB::raw('IFNULL(existingControl.`value`,0) AS existingControl')
        );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerContractorId') {
                        $qDetail->where(SqlHelper::getPreparedField('wg_customer_contractor.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_customer_safety_inspection');
        $qSub->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_safety_inspection.contractorType', '=', 'wg_customer_contractor.contractor_type_id');

        })->join('wg_customer_contractor_safety_inspection', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');
            $join->on('wg_customer_contractor_safety_inspection.customer_id', '=', 'wg_customer_contractor.contractor_id');

        })->join('wg_customer_safety_inspection_list', function ($join) {
            $join->on('wg_customer_safety_inspection_list.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');

        })->join('wg_customer_safety_inspection_config_list', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list.id', '=', 'wg_customer_safety_inspection_list.customer_safety_inspection_config_list_id');

        })->join('wg_customer_safety_inspection_config_list_group', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');

        })->join('wg_customer_safety_inspection_config_list_item', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_item.customer_safety_inspection_config_list_group_id', '=', 'wg_customer_safety_inspection_config_list_group.id');

        })->join('wg_customer_contractor_safety_inspection_list_item', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id', '=', 'wg_customer_safety_inspection_list.id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id', '=', 'wg_customer_safety_inspection_config_list_item.id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_contractor_id', '=', 'wg_customer_contractor.id');

        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id', '=', 'detail.customer_safety_inspection_config_list_item_id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_contractor_id', '=', 'detail.customer_contractor_id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id', '=', 'detail.customer_safety_inspection_list_id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.customer_contractor_safety_inspection_id', '=', 'detail.customer_contractor_safety_inspection_id');
            $join->on('wg_customer_contractor_safety_inspection_list_item.period', '=', 'detail.period');

        })->select(
            'wg_customer_safety_inspection_config_list_group.id',
            'wg_customer_safety_inspection_config_list_group.description',
            'wg_customer_safety_inspection_config_list_group.sort',
            'wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id AS customerSafetyInspectionConfigListId',
            'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id AS customerContractorId',
            'wg_customer_contractor_safety_inspection_list_item.period',
            DB::raw('COUNT(*) questions'),
            DB::raw('SUM(CASE WHEN detail.dangerousness = 0 AND detail.existingControl = 0  THEN 0 ELSE 1 END) AS answers'),
            DB::raw('SUM(detail.dangerousness * detail.existingControl) AS total')
        )
            ->whereRaw("wg_customer_safety_inspection_config_list.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_group.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_item.`isActive` = 1")
            ->groupBy(
                'wg_customer_safety_inspection_config_list_group.description',
                'wg_customer_safety_inspection_config_list_group.id',
                'wg_customer_contractor_safety_inspection_list_item.period',
                'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id'
            )
            ->mergeBindings($qDetail);


        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as category")));

        $query->groupBy('category.id')->mergeBindings($qSub);


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    //if ($item->field == 'cycleId') {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    //}
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

        // $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns)->total() : (is_array($data = $query->get($this->columns)) ? count($data) : $data->count());
        // $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->take($criteria->take)->skip($criteria->skip)->get() : $query->get($this->columns));

        // return $result;

        return $this->get($query, $criteria);
    }

    public function allQuestion($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_contractor_safety_inspection_list_item.id",
            "description" => "wg_customer_safety_inspection_config_list_item.description",
            "period" => "wg_customer_contractor_safety_inspection_list_item.period",
            "groupId" => "wg_customer_safety_inspection_config_list_group.id as groupId",

            "actionValue" => "wg_safety_inspection_action.value as actionValue",
            "actionText" => "wg_safety_inspection_action.item as actionText",
            "actionId" => "wg_safety_inspection_action.id as actionId",

            "dangerousnessId" => "dangerousness.id as dangerousnessId",
            "dangerousnessDescription" => "dangerousness.description as dangerousnessDescription",
            "dangerousnessType" => "dangerousness.type as dangerousnessType",
            "dangerousnessValue" => "dangerousness.value AS dangerousnessValue",

            "priorityId" => "priority.id as priorityId",
            "priorityDescription" => "priority.description as priorityDescription",
            "priorityType" => "priority.type as priorityType",
            "priorityValue" => "priority.value AS priorityValue",

            "existingControlId" => "existingControl.id as existingControlId",
            "existingControlDescription" => "existingControl.description as existingControlDescription",
            "existingControlType" => "existingControl.type as existingControlType",
            "existingControlValue" => "existingControl.value AS existingControlValue",

            "calculate" => DB::raw("(IFNULL(existingControl.value, 0) * IFNULL(dangerousness.value, 0)) AS calculate"),

            "customerContractorId" => "wg_customer_contractor_safety_inspection_list_item.customer_contractor_id AS customerContractorId",
            "customerSafetyInspectionListId" => "wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id as customerSafetyInspectionListId",
            "customerSafetyInspectionConfigListId" => 'wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id AS customerSafetyInspectionConfigListId',
            "customerSafetyInspectionConfigListItemId" => "wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id as customerSafetyInspectionConfigListItemId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $query = $this->query();

        $query->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_contractor.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id');

        })->join('wg_customer_safety_inspection_config_list_item', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_item.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id');

        })->join('wg_customer_safety_inspection_config_list_group', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_group.id', '=', 'wg_customer_safety_inspection_config_list_item.customer_safety_inspection_config_list_group_id');

        })->join('wg_customer_safety_inspection_config_list', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list.id', '=', 'wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id');

        })->join('wg_customer_safety_inspection_list', function ($join) {
            $join->on('wg_customer_safety_inspection_list.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id');
            $join->on('wg_customer_safety_inspection_list.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');

        })->join('wg_customer_safety_inspection', function ($join) {
            $join->on('wg_customer_safety_inspection.customer_id', '=', 'wg_customer_contractor.customer_id');
            $join->on('wg_customer_safety_inspection.contractorType', '=', 'wg_customer_contractor.contractor_type_id');

        })->join('wg_customer_contractor_safety_inspection', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');
            $join->on('wg_customer_contractor_safety_inspection.customer_id', '=', 'wg_customer_contractor.contractor_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_safety_inspection_action')), function ($join) {
            $join->on('wg_customer_contractor_safety_inspection_list_item.action', '=', 'wg_safety_inspection_action.value');

        })->leftjoin(DB::raw('wg_customer_safety_inspection_config_list_validation AS dangerousness'), function ($join) {
            $join->on('dangerousness.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('dangerousness.id', '=', 'wg_customer_contractor_safety_inspection_list_item.dangerousnessValue');

        })->leftjoin(DB::raw('wg_customer_safety_inspection_config_list_validation AS priority'), function ($join) {
            $join->on('priority.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('priority.id', '=', 'wg_customer_contractor_safety_inspection_list_item.priorityValue');

        })->leftjoin(DB::raw('wg_customer_safety_inspection_config_list_validation AS existingControl'), function ($join) {
            $join->on('existingControl.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('existingControl.id', '=', 'wg_customer_contractor_safety_inspection_list_item.existingControlValue');

        });

        $query->whereRaw("wg_customer_safety_inspection_config_list.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_group.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_item.`isActive` = 1");

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

    public function allDangerousness($criteria)
    {
        $this->setColumns([
            "period" => "wg_customer_contractor_safety_inspection_list_item.period",
            "description" => "dangerousness.description",
            DB::raw('COUNT(*) quantity'),
            "customerContractorId" => "wg_customer_contractor_safety_inspection_list_item.customer_contractor_id AS customerContractorId",
            "customerSafetyInspectionListId" => "wg_customer_safety_inspection_config_list.id as customerSafetyInspectionListId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            //$this->addSortColumn('id');
        }

        $query = $this->query();

        $query->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_contractor.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id');

        })->join('wg_customer_safety_inspection_config_list_item', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_item.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id');

        })->join('wg_customer_safety_inspection_config_list_group', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_group.id', '=', 'wg_customer_safety_inspection_config_list_item.customer_safety_inspection_config_list_group_id');

        })->join('wg_customer_safety_inspection_config_list', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list.id', '=', 'wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id');

        })->join('wg_customer_safety_inspection_list', function ($join) {
            $join->on('wg_customer_safety_inspection_list.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id');
            $join->on('wg_customer_safety_inspection_list.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');

        })->join('wg_customer_safety_inspection', function ($join) {
            $join->on('wg_customer_safety_inspection.customer_id', '=', 'wg_customer_contractor.customer_id');
            $join->on('wg_customer_safety_inspection.contractorType', '=', 'wg_customer_contractor.contractor_type_id');

        })->join('wg_customer_contractor_safety_inspection', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');
            $join->on('wg_customer_contractor_safety_inspection.customer_id', '=', 'wg_customer_contractor.contractor_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_safety_inspection_action')), function ($join) {
            $join->on('wg_customer_contractor_safety_inspection_list_item.action', '=', 'wg_safety_inspection_action.value');

        })->join(DB::raw('wg_customer_safety_inspection_config_list_validation AS dangerousness'), function ($join) {
            $join->on('dangerousness.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');
            $join->on('dangerousness.id', '=', 'wg_customer_contractor_safety_inspection_list_item.dangerousnessValue');

        })->groupBy(
            'wg_customer_safety_inspection_config_list.id',
            'wg_customer_contractor_safety_inspection_list_item.dangerousnessValue',
            'wg_customer_contractor_safety_inspection_list_item.period',
            'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id'
        );

        $query->whereRaw("wg_customer_safety_inspection_config_list.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_group.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_item.`isActive` = 1");

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
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allAction($criteria)
    {
        $this->setColumns([
            "period" => "wg_customer_contractor_safety_inspection_list_item.period",
            "description" => "wg_safety_inspection_action.item as description",
            DB::raw('COUNT(*) quantity'),
            "customerContractorId" => "wg_customer_contractor_safety_inspection_list_item.customer_contractor_id AS customerContractorId",
            "customerSafetyInspectionListId" => "wg_customer_safety_inspection_config_list.id as customerSafetyInspectionListId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            //$this->addSortColumn('id');
        }

        $query = $this->query();

        $query->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_contractor.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id');

        })->join('wg_customer_safety_inspection_config_list_item', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_item.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_config_list_item_id');

        })->join('wg_customer_safety_inspection_config_list_group', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list_group.id', '=', 'wg_customer_safety_inspection_config_list_item.customer_safety_inspection_config_list_group_id');

        })->join('wg_customer_safety_inspection_config_list', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list.id', '=', 'wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id');

        })->join('wg_customer_safety_inspection_list', function ($join) {
            $join->on('wg_customer_safety_inspection_list.id', '=', 'wg_customer_contractor_safety_inspection_list_item.customer_safety_inspection_list_id');
            $join->on('wg_customer_safety_inspection_list.customer_safety_inspection_config_list_id', '=', 'wg_customer_safety_inspection_config_list.id');

        })->join('wg_customer_safety_inspection', function ($join) {
            $join->on('wg_customer_safety_inspection.customer_id', '=', 'wg_customer_contractor.customer_id');
            $join->on('wg_customer_safety_inspection.contractorType', '=', 'wg_customer_contractor.contractor_type_id');

        })->join('wg_customer_contractor_safety_inspection', function ($join) {
            $join->on('wg_customer_contractor_safety_inspection.customer_safety_inspection_id', '=', 'wg_customer_safety_inspection.id');
            $join->on('wg_customer_contractor_safety_inspection.customer_id', '=', 'wg_customer_contractor.contractor_id');

        })->join(DB::raw(SystemParameter::getRelationTable('wg_safety_inspection_action')), function ($join) {
            $join->on('wg_customer_contractor_safety_inspection_list_item.action', '=', 'wg_safety_inspection_action.value');

        })->groupBy(
            'wg_customer_safety_inspection_config_list.id',
            'wg_customer_contractor_safety_inspection_list_item.action',
            'wg_customer_contractor_safety_inspection_list_item.period',
            'wg_customer_contractor_safety_inspection_list_item.customer_contractor_id'
        );

        $query->whereRaw("wg_customer_safety_inspection_config_list.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_group.`isActive` = 1")
            ->whereRaw("wg_customer_safety_inspection_config_list_item.`isActive` = 1");

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
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

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

        $entityModel->dangerousnessValue = isset($entity->dangerousness) && $entity->dangerousness ? $entity->dangerousness->id : null;
        $entityModel->existingControlValue = isset($entity->existingControl) && $entity->existingControl ? $entity->existingControl->id : null;
        $entityModel->priorityValue = isset($entity->priority) ? $entity->priority->id : null;
        $entityModel->action = isset($entity->action) && $entity->action ? $entity->action->value : null;

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

    public function batch($entities)
    {
        foreach($entities as $entity) {
            $this->insertOrUpdate($entity);
        }

        return true;
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

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }

    public function getChartBar($criteria)
    {
        return $this->service->getChartBar($criteria);
    }

    public function getChartPie($criteria)
    {
        return $this->service->getChartPie($criteria);
    }

    public function getStats($criteria)
    {
        return $this->service->getStats($criteria);
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

                if (isset($model->actionId) && isset($model->actionText)) {
                    $model->action = ['id' => $model->actionId, 'item' => $model->actionText, 'value' => $model->actionValue];
                }

                if (isset($model->dangerousnessValue) && isset($model->dangerousnessId)) {
                    $model->dangerousness = [
                        'id' => $model->dangerousnessId,
                        'value' => $model->dangerousnessValue,
                        'description' => $model->dangerousnessDescription,
                        'type' => $model->dangerousnessType
                    ];
                }

                if (isset($model->priorityValue) && isset($model->priorityId)) {
                    $model->priority = [
                        'id' => $model->priorityId,
                        'value' => $model->priorityValue,
                        'description' => $model->priorityDescription,
                        'type' => $model->priorityType
                    ];
                }

                if (isset($model->existingControlValue) && isset($model->existingControlId)) {
                    $model->existingControl = [
                        'id' => $model->existingControlId,
                        'value' => $model->existingControlValue,
                        'description' => $model->existingControlDescription,
                        'type' => $model->existingControlType
                    ];
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
}
