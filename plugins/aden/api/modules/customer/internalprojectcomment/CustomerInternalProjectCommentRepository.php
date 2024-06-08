<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InternalProjectComment;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerInternalProjectCommentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerInternalProjectCommentModel());

        $this->service = new CustomerInternalProjectCommentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "comment" => "wg_customer_internal_project_comment.comment",            
            "createdBy" => "users.name AS createdBy",            
            "createdAt" => "wg_customer_internal_project_comment.created_at",
            "id" => "wg_customer_internal_project_comment.id",
            "customerInternalProjectId" => "wg_customer_internal_project_comment.customer_internal_project_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin("users", function ($join) {
            $join->on('users.id', '=', 'wg_customer_internal_project_comment.created_by');
        });

        $this->applyCriteria($query, $criteria);

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

        $entityModel->customerInternalProjectId = $entity->customerInternalProjectId;
        $entityModel->comment = $entity->comment;
        $entityModel->type = $entity->type;

        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
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

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerInternalProjectId = $model->customerInternalProjectId;
            $entity->comment = $model->comment;
            $entity->type = $model->type;

            return $entity;
        } else {
            return null;
        }
    }
}
