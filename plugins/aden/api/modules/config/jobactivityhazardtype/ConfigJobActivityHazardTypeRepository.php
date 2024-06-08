<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Config\JobActivityHazardType;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ConfigJobActivityHazardTypeRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigJobActivityHazardTypeModel());

        $this->service = new ConfigJobActivityHazardTypeService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_config_job_activity_hazard_type.id",
            "classificationId" => "wg_config_job_activity_hazard_type.classification_id",
            "name" => "wg_config_job_activity_hazard_type.name",
            "code" => "wg_config_job_activity_hazard_type.code",
            "createdby" => "wg_config_job_activity_hazard_type.createdBy",
            "updatedby" => "wg_config_job_activity_hazard_type.updatedBy",
            "createdAt" => "wg_config_job_activity_hazard_type.created_at",
            "updatedAt" => "wg_config_job_activity_hazard_type.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_config_job_activity_hazard_type.parent_id', '=', 'tableParent.id');
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

        $entityModel->classificationId = $entity->classificationId ? $entity->classificationId->id : null;
        $entityModel->name = $entity->name;
        $entityModel->code = $entity->code;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;


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

    public function getList($criteria)
    {
        return $this->service->getList($criteria);
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->classificationId = $model->classificationId;
            $entity->name = $model->name;
            $entity->code = $model->code;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }
}