<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\ResourceLibrary;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;

class ResourceLibraryRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ResourceLibraryModel());

        $this->service = new ResourceLibraryService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Fecha Creación", "name" => "createdAt"],
            ["alias" => "Tipo", "name" => "type"],
            ["alias" => "Fecha Recurso", "name" => "dateof"],
            ["alias" => "Nombre", "name" => "name"],
            ["alias" => "Autor/Emisor", "name" => "author"],
            ["alias" => "Asunto", "Fecha Creación" => "subject"],
            ["alias" => "Descripción", "Actualizado Por" => "description"],
            ["alias" => "Estado", "Actualizado Por" => "status"],
        ];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_resource_library.id",
            "createdAt" => "wg_resource_library.created_at as createdAt",
            "type" => "resource_library_type.item AS type",
            "dateof" => "wg_resource_library.dateOf",
            "name" => "wg_resource_library.name",
            "author" => "wg_resource_library.author",
            "subject" => "wg_resource_library.subject",
            "description" => "wg_resource_library.description",
            "status" => "estado.item AS status",
            "updatedBy" => "users.name AS updatedBy",
            /*'document' => DB::raw("IF (
                system_files.disk_name IS NOT NULL,
                CONCAT_WS(
                    \"/\",
                    '{$urlImages}',
                    SUBSTR(system_files.disk_name, 1, 3),
                    SUBSTR(system_files.disk_name, 4, 3),
                    SUBSTR(system_files.disk_name, 7, 3),
                    system_files.disk_name
                ),
                NULL
            ) as documentUrl"),*/
            "isActive" => "wg_resource_library.isActive",
            "typeValue" => "wg_resource_library.type as typeValue"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('resource_library_type')), function ($join) {
            $join->on('wg_resource_library.type', '=', 'resource_library_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_resource_library.isActive', '=', 'estado.value');

        })/*->leftjoin(DB::raw(ResourceLibraryModel::getSystemFile()), function ($join) {
            $join->on('wg_resource_library.id', '=', 'system_files.attachment_id');

        })*/->leftjoin("users", function ($join) {
            $join->on('wg_resource_library.updatedBy', '=', 'users.id');

        });

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, ResourceLibraryModel::CLASS_NAME);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->dateof = $entity->dateof ? Carbon::parse($entity->dateof)->timezone('America/Bogota') : null;
        $entityModel->name = $entity->name;
        $entityModel->author = $entity->author;
        $entityModel->subject = $entity->subject;
        $entityModel->description = $entity->description;
        $entityModel->keyword = $entity->keyword;
        $entityModel->isactive = $entity->isactive;

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

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->type = $model->getType();
            $entity->dateof = $model->dateof ? Carbon::parse($model->dateof) : null;
            $entity->name = $model->name;
            $entity->author = $model->author;
            $entity->subject = $model->subject;
            $entity->description = $model->description;
            $entity->keyword = $model->keyword;

            return $entity;
        } else {
            return null;
        }
    }
}
