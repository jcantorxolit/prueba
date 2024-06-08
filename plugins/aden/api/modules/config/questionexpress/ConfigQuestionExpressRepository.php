<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Config\QuestionExpress;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ConfigQuestionExpressRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigQuestionExpressModel());

        $this->service = new ConfigQuestionExpressService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_config_question_express.id",
            "classificationExpressId" => "wg_config_question_express.classification_express_id",
            "description" => "wg_config_question_express.description",
            "priority" => "wg_config_question_express.priority",
            "isMaster" => "wg_config_question_express.is_master",
            "sort" => "wg_config_question_express.sort",
            "isActive" => "wg_config_question_express.is_active",
            "createdAt" => "wg_config_question_express.created_at",
            "updatedAt" => "wg_config_question_express.updated_at",
            "createdBy" => "wg_config_question_express.created_by",
            "updatedBy" => "wg_config_question_express.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_config_question_express.parent_id', '=', 'tableParent.id');
		}
		*/


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

        $entityModel->classificationExpressId = $entity->classificationExpressId ? $entity->classificationExpressId->id : null;
        $entityModel->description = $entity->description;
        $entityModel->priority = $entity->priority ? $entity->priority->value : null;
        $entityModel->isMaster = $entity->isMaster == 1;
        $entityModel->sort = $entity->sort;
        $entityModel->isActive = $entity->isActive == 1;


        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
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
            $entity->classificationExpressId = $model->classificationExpressId;
            $entity->description = $model->description;
            $entity->priority = $model->getPriority();
            $entity->isMaster = $model->isMaster;
            $entity->sort = $model->sort;
            $entity->isActive = $model->isActive;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;
            $entity->createdBy = $model->createdBy;
            $entity->updatedBy = $model->updatedBy;


            return $entity;
        } else {
            return null;
        }
    }
}
