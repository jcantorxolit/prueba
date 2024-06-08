<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ManagementProgram;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerManagementProgramRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerManagementProgramModel());

        $this->service = new CustomerManagementProgramService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_management_program.id",
            "managementId" => "wg_customer_management_program.management_id",
            "programId" => "wg_customer_management_program.program_id",
            "programEconomicSectorId" => "wg_customer_management_program.program_economic_sector_id",
            "customerWorkplaceId" => "wg_customer_management_program.customer_workplace_id",
            "active" => "wg_customer_management_program.active",
            "createdBy" => "wg_customer_management_program.created_by",
            "updatedBy" => "wg_customer_management_program.updated_by",
            "createdAt" => "wg_customer_management_program.created_at",
            "updatedAt" => "wg_customer_management_program.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_management_program.parent_id', '=', 'tableParent.id');
		}
		*/


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        return true;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->managementId = $entity->managementId;
        $entityModel->programId = 0;
        $entityModel->programEconomicSectorId = $entity->programEconomicSector ? $entity->programEconomicSector->id : null;
        $entityModel->customerWorkplaceId = $entity->customerWorkplace ? $entity->customerWorkplace->id : null;
        $entityModel->active = $entity->active;

        if ($isNewRecord) {            
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

    public static function create($entity, $parentId)
    {
        $entity->managementId = $parentId;
        return (new self)->insertOrUpdate($entity);
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
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->managementId = $model->managementId;
            $entity->programId = $model->programId;
            $entity->programEconomicSectorId = $model->programEconomicSectorId;
            $entity->customerWorkplaceId = $model->customerWorkplaceId;
            $entity->active = $model->active;
            $entity->createdBy = $model->createdBy;
            $entity->updatedBy = $model->updatedBy;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }
}
