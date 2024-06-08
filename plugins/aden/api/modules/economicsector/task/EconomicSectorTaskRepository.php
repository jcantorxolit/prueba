<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\EconomicSector\Task;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Classes\Criteria;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class EconomicSectorTaskRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new EconomicSectorTaskModel());

        $this->service = new EconomicSectorTaskService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_economic_sector_task.id",
            "name" => "wg_economic_sector_task.name",
            "isActive" => "wg_economic_sector_task.is_active",
            "economicSectorId" => "wg_economic_sector_task.economic_sector_id",
        ]);

        $this->parseCriteria(null);

        $query = $this->query();

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

        $entityModel->economicSectorId = $entity->economicSectorId;
        $entityModel->name = $entity->name;
        $entityModel->isActive = $entity->isActive == 1;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;            
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
        $economicSectorId = 0;
        foreach ($entities as $entity) {
            if (!empty(trim($entity->name))) {
                $economicSectorId = $entity->economicSectorId;
                $this->insertOrUpdate($entity);
            }
        }

        $criteria = new Criteria();        
        $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
            ["field" => 'economicSectorId', "operator" => 'eq', 'value' => $economicSectorId]
        ]);

        return $this->all($criteria);
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
            $entity->economicSectorId = $model->economicSectorId;
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
}
