<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\ProgramManagement\EconomicSector;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ProgramManagementEconomicSectorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ProgramManagementEconomicSectorModel());

        $this->service = new ProgramManagementEconomicSectorService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_program_management_economic_sector.id",
            "economicSector" => "wg_economic_sector.name as economicSector",
            "program" => "wg_program_management.name AS program",
            "isActive" => "wg_program_management_economic_sector.is_active",
            "createdBy" => "users.name",
            "createdAt" => "wg_program_management_economic_sector.created_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();
        
		$query->join("wg_program_management", function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');
		})->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');
		})->leftjoin("users", function ($join) {
            $join->on('users.id', '=', 'wg_program_management_economic_sector.created_by');
		});
		
        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        return $this->model->where('economic_sector_id', $entity->economicSector->id)->where('program_id', $entity->program->id)->count() == 0;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->programId = $entity->program ? $entity->program->id : null;
        $entityModel->economicSectorId = $entity->economicSector ? $entity->economicSector->id : null;
        $entityModel->isActive = $entity->isActive;


        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }
        
        return $entityModel;
    }
    
    public function canDelete($id)
    {        
        return DB::table('wg_customer_management_program')->where('program_economic_sector_id', $id)->count() == 0;
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
            $entity->programId = $model->programId;
            $entity->economicSectorId = $model->economicSectorId;
            $entity->isActive = $model->isActive == 1;
            
            return $entity;
        } else {
            return null;
        }
    }
}
