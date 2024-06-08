<?php

namespace AdeN\Api\Modules\Customer\ProjectComments;

use AdeN\Api\Classes\BaseRepository;

use DB;
use Exception;
use Log;

class CustomerProjectCommentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerProjectCommentModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "comment" => "wg_customer_project_comments.comment",
            "createdBy" => "users.name AS createdBy",            
            "createdAt" => "wg_customer_project_comments.created_at",
            "id" => "wg_customer_project_comments.id",
            "customerProjectId" => "wg_customer_project_comments.customer_project_id as customerProjectId",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->leftjoin("users", 'users.id', '=', 'wg_customer_project_comments.created_by');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function insertOrUpdate($entity)
    {
        $authUser = $this->getAuthUser();
        $userId = $authUser->id ?? 1;

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $entityModel->customerProjectId = $entity->customerProjectId;
        $entityModel->comment = $entity->comment;
        $entityModel->type = $entity->type;

        if (empty($entityModel->id)) {
            $entityModel->createdBy = $userId;
        }

        $entityModel->updatedBy = $userId;
        $entityModel->save();

        return $this->parseModelWithRelations($entityModel);
    }


    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }


    public function parseModelWithRelations(CustomerProjectCommentModel $model)
    {
        $entity = new \stdClass();
        $entity->id = $model->id;
        $entity->customerProjectId = $model->customerProjectId;
        $entity->comment = $model->comment;
        $entity->type = $model->type;
        return $entity;
    }
}
