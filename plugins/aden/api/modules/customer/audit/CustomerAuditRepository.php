<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Audit;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use Wgroup\SystemParameter\SystemParameter;

class CustomerAuditRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerAuditModel());

        $this->service = new CustomerAuditService();
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
            "createdAt" => "wg_customer_audit.created_at",
            "userType" => "wg_user_type.item AS userType",
            "email" => "users.email",
            "action" => "wg_customer_audit.action",
            "observation" => "wg_customer_audit.observation",
            "customerId" => "wg_customer_audit.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_user_type')), function ($join) {
            $join->on('wg_customer_audit.user_type', '=', 'wg_user_type.value');
        })->leftjoin('users', function ($join) {
            $join->on('users.id', '=', 'wg_customer_audit.user_id');
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

        $userType = $authUser ? $authUser->wg_type : 'system';
        $userId = $authUser ? $authUser->id : 0;

        $entityModel->customerId = $entity->customerId;
        $entityModel->modelName = $entity->modelName;
        $entityModel->modelId = $entity->customerId;
        $entityModel->userType = isset($entity->userType) ? $entity->userType : $userType;
        $entityModel->userId = isset($entity->userId) ? $entity->userId : $userId;
        $entityModel->action = $entity->action;
        $entityModel->observation = $entity->observation;
        $entityModel->date = $entity->date ? Carbon::parse($entity->date) : null;

        if ($isNewRecord) {
            $entityModel->save();
        } else {
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public static function createMatrix($entityModel)
    {
        $matrixType = [
            "G" => "GTC45",
            "E" => "Express",
        ];

        if ($entityModel->isDirty('matrixType')) {
            $newAudit = new \stdClass;
            $newAudit->id = 0;
            $newAudit->customerId = $entityModel->id;
            $newAudit->modelName = "Cliente";
            $newAudit->modelId = $entityModel->id;
            $newAudit->action = "Editar Matrix";
            $newAudit->observation = "Se realiza el cambio exitoso de la matriz de peligros a: (" . $matrixType[$entityModel->matrixType] . ")";
            $newAudit->ip = null;
            $newAudit->date = Carbon::now('America/Bogota');
            (new self)->insertOrUpdate($newAudit);
        }
    }

    public static function create($customerId, $modelName, $action, $observation, $userType, $userId)
    {
        $entity = new \stdClass();
        $entity->id = 0;
        $entity->customerId = $customerId;
        $entity->modelName = $modelName;
        $entity->action = $action;
        $entity->observation = $observation;
        $entity->userType = $userType;
        $entity->userId = $userId;
        $entity->date = Carbon::now('America/Bogota');

        return (new self)->insertOrUpdate($entity);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->modelName = $model->modelName;
            $entity->modelId = $model->modelId;
            $entity->userType = $model->userType;
            $entity->userId = $model->userId;
            $entity->action = $model->action;
            $entity->observation = $model->observation;
            $entity->ip = $model->ip;
            $entity->date = $model->date ? Carbon::parse($model->date) : null;

            return $entity;
        } else {
            return null;
        }
    }
}
