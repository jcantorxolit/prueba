<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\DocumentSecurityUser;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\CustomerModel;
use Carbon\Carbon;
use Exception;
use DB;
use AdeN\Api\Helpers\SqlHelper;

class CustomerDocumentSecurityUserRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerDocumentSecurityUserModel());

        $this->service = new CustomerDocumentSecurityUserService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "userName" => "user.name AS userName",
            "userType" => "user.type AS userType",
            "userEmail" => "user.email AS userEmail",
            "userId" => "user.id AS userId",                        
            "updatedAt" => "wg_customer_document_security_user.updated_at AS updatedAt",            
            "updatedby" => "wg_customer_document_security_user.updatedBy",
            "id" => "wg_customer_document_security_user.id",            
            "isActive" => "wg_customer_document_security_user.isActive",
        ]);

        $this->parseCriteria($criteria);

        $qAgentOrUser = CustomerModel::getDocumentSecurityAgentAndUserRaw($criteria);

        $qSecurityUser = DB::table('wg_customer_document_security_user')
            ->leftjoin('users', function ($join) {
                $join->on('users.id', '=', 'wg_customer_document_security_user.updatedBy');

            })
            ->select(
                "wg_customer_document_security_user.id",
                "wg_customer_document_security_user.user_id",
                "wg_customer_document_security_user.isActive",
                "wg_customer_document_security_user.updated_at",
                "users.name AS updatedBy"
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $qSecurityUser->where('wg_customer_document_security_user.customer_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else {
                        $qSecurityUser->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw("({$qAgentOrUser->toSql()}) as user")));

        /* Example relation */
        $query->leftjoin(DB::raw("({$qSecurityUser->toSql()}) as wg_customer_document_security_user"), function ($join) {
            $join->on('user.id', '=', 'wg_customer_document_security_user.user_id');

        })
        ->mergeBindings($qAgentOrUser)
        ->mergeBindings($qSecurityUser);

        if ($criteria != null) {
            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }
        
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->user_id = $entity->userId;
        $entityModel->documentType = $entity->documentType ? $entity->documentType->value : null;
        $entityModel->origin = $entity->documentType ? $entity->documentType->origin : null;
        $entityModel->isActive = $entity->isActive;
        
        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updated_at = Carbon::now();
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

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customer_id;
            $entity->userId = $model->user_id;
            $entity->documentType = $model->documentType;
            $entity->origin = $model->origin;
            $entity->isActive = $model->isActive == 1;

            return $entity;
        } else {
            return null;
        }
    }
}
