<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Report;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class ReportRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ReportModel());

        $this->service = new ReportService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_report.id",            
            "name" => "wg_report.name",
            "description" => "wg_report.description",
            "status" => "estado.item as status",
            "allowAgent" => "wg_report.allowAgent",
            "allowCustomer" => "wg_report.allowCustomer",
            "isActive" => "wg_report.isActive",
            "module" => "wg_collection_data.module",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
		$query->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('estado.value', '=', 'wg_report.isActive');

		})->join("wg_collection_data", function ($join) {
            $join->on('wg_collection_data.id', '=', 'wg_report.collection_id');

		})->where('wg_report.isAutomatic', 0);	

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

        $entityModel->collectionId = $entity->collectionId;
        $entityModel->name = $entity->name;
        $entityModel->description = $entity->description;
        $entityModel->isactive = $entity->isactive == 1;
        $entityModel->allowagent = $entity->allowagent == 1;
        $entityModel->allowcustomer = $entity->allowcustomer == 1;
        $entityModel->collectionChartId = $entity->collectionChartId;
        $entityModel->charttype = $entity->charttype;
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

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->collectionId = $model->collectionId;
            $entity->name = $model->name;
            $entity->description = $model->description;
            $entity->isactive = $model->isactive;
            $entity->allowagent = $model->allowagent;
            $entity->allowcustomer = $model->allowcustomer;
            $entity->collectionChartId = $model->collectionChartId;
            $entity->charttype = $model->charttype;
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
