<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\EconomicSector;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class EconomicSectorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new EconomicSectorModel());

        $this->service = new EconomicSectorService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_economic_sector.id",
            "name" => "wg_economic_sector.name",
            "isActive" => "wg_economic_sector.is_active",
            "createdAt" => "wg_economic_sector.created_at",
            "updatedAt" => "wg_economic_sector.updated_at",
            "createdBy" => "wg_economic_sector.created_by",
            "updatedBy" => "wg_economic_sector.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_economic_sector.parent_id', '=', 'tableParent.id');
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

        $entityModel->name = $entity->name;
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
            $entity->name = $model->name;
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

    public function getList()
    {
        return $this->model->where('is_active', 1)->get();
    }
}
