<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Config\General;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ConfigGeneralRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigGeneralModel());

        $this->service = new ConfigGeneralService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_config_general.id",
            "type" => "wg_config_general.type",
            "value" => "wg_config_general.value",
            "code" => "wg_config_general.code",
            "name" => "wg_config_general.name",
            "format" => "wg_config_general.format",
            "description" => "wg_config_general.description",
            "isMandatory" => "wg_config_general.is_mandatory",
            "isActive" => "wg_config_general.is_active",
            "createdAt" => "wg_config_general.created_at",
            "createdBy" => "wg_config_general.created_by",
            "updatedAt" => "wg_config_general.updated_at",
            "updatedBy" => "wg_config_general.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_config_general.parent_id', '=', 'tableParent.id');
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

        $entityModel->type = $entity->type;
        $entityModel->value = $entity->value;
        $entityModel->code = $entity->code ? $entity->code->value : null;
        $entityModel->name = $entity->name;
        $entityModel->format = $entity->format;
        $entityModel->description = $entity->description;
        $entityModel->isMandatory = $entity->isMandatory == 1;
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

    public function getListND()
    {
        return $this->service->getList('ND_SPECIAL_MATRIX');
    }

    public function getListNE()
    {
        return $this->service->getList('NE_SPECIAL_MATRIX');
    }

    public function getListNC()
    {
        return $this->service->getList('NC_SPECIAL_MATRIX');
    }

    public function getListProbabilityLevel()
    {
        return array_map(function($item) {
            return [
                "value" => (int) $item->qualification,
                "text" => $item->name,
                "description" => $item->justification,
                "color" => $item->color
            ];
        }, $this->service->getList('PROBABILITY_LEVEL')->toArray());
    }

    public function getListRiskLevel()
    {
        return array_map(function($item) {
            return [
                "qualification" => $item->qualification,
                "text" => $item->name,
                "description" => $item->justification
            ];
        }, $this->service->getList('RISK_LEVEL')->toArray());
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->type = $model->type;
            $entity->value = $model->value;
            $entity->code = $model->getCode();
            $entity->name = $model->name;
            $entity->format = $model->format;
            $entity->description = $model->description;
            $entity->isMandatory = $model->isMandatory;
            $entity->isActive = $model->isActive;
            $entity->createdAt = $model->createdAt;
            $entity->createdBy = $model->createdBy;
            $entity->updatedAt = $model->updatedAt;
            $entity->updatedBy = $model->updatedBy;


            return $entity;
        } else {
            return null;
        }
    }
}
