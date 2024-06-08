<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\SafetyInspectionListObservation;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\Customer\CustomerModel;
use Wgroup\SystemParameter\SystemParameter;

class CustomerSafetyInspectionListObservationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerSafetyInspectionListObservationModel());

        $this->service = new CustomerSafetyInspectionListObservationService();
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
            "list" => "wg_customer_safety_inspection_config_list.name as list",
            "observation" => "wg_customer_safety_inspection_list_observation.observation",
            "user" => "users.name AS user",
            "createdAt" => "wg_customer_safety_inspection_list_observation.created_at",
            "id" => "wg_customer_safety_inspection_list_observation.id",
            "customerSafetyInspectionId" => "wg_customer_safety_inspection_list.customer_safety_inspection_id AS customerSafetyInspectionId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join('wg_customer_safety_inspection_list', function ($join) {
            $join->on('wg_customer_safety_inspection_list.id', '=', 'wg_customer_safety_inspection_list_observation.customer_safety_inspection_list_id');

        })->join('wg_customer_safety_inspection_config_list', function ($join) {
            $join->on('wg_customer_safety_inspection_config_list.id', '=', 'wg_customer_safety_inspection_list.customer_safety_inspection_config_list_id');

        })->leftjoin('users', function ($join) {
            $join->on('users.id', '=', 'wg_customer_safety_inspection_list_observation.created_by');

        });

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

        $entityModel->customerSafetyInspectionListId = $entity->customerSafetyInspectionList ? $entity->customerSafetyInspectionList->customerSafetyInspectionListId : null;
        $entityModel->observation = $entity->observation;

        if ($isNewRecord) {
            $entityModel->isActive = true;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
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
}
