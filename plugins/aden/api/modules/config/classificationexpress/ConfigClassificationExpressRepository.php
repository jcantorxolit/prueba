<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Config\ClassificationExpress;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class ConfigClassificationExpressRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigClassificationExpressModel());

        $this->service = new ConfigClassificationExpressService();
    }

    public function all($criteria)
    {        
        $this->setColumns([
"id" => "wg_config_classification_express.id",
"parentId" => "wg_config_classification_express.parent_id",
"name" => "wg_config_classification_express.name",
"type" => "wg_config_classification_express.type",
"sort" => "wg_config_classification_express.sort",
"isActive" => "wg_config_classification_express.is_active",
"code" => "wg_config_classification_express.code",
"createdAt" => "wg_config_classification_express.created_at",
"updatedAt" => "wg_config_classification_express.updated_at",
"createdBy" => "wg_config_classification_express.created_by",
"updatedBy" => "wg_config_classification_express.updated_by",
]);

        $this->parseCriteria($criteria);

        $query = $this->query();

		/* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_config_classification_express.parent_id', '=', 'tableParent.id');
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

        $entityModel->parentId = $entity->parentId ? $entity->parentId->id : null;
$entityModel->name = $entity->name;
$entityModel->type = $entity->type ? $entity->type->value : null;
$entityModel->sort = $entity->sort;
$entityModel->isActive = $entity->isActive == 1;
$entityModel->code = $entity->code ? $entity->code->value : null;


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
$entity->parentId = $model->parentId;
$entity->name = $model->name;
$entity->type = $model->getType();
$entity->sort = $model->sort;
$entity->isActive = $model->isActive;
$entity->code = $model->getCode();
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