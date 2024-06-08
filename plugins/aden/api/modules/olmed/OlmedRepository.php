<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Olmed;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;

class OlmedRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new OlmedModel());

        $this->service = new OlmedService();
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
        })/*->leftjoin(DB::raw(OlmedModel::getSystemFile()), function ($join) {
            $join->on('wg_resource_library.id', '=', 'system_files.attachment_id');

        })*/->leftjoin("users", function ($join) {
            $join->on('wg_resource_library.updatedBy', '=', 'users.id');
        });

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, OlmedModel::CLASS_NAME);
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

    public function parseModelWithRelations($id)
    {
        $response = [
            "5014109457" => [
                "ESTADO" => "OK",
                "ERROR" => "0",
                "DNI" => "D97C44B3-0A59-45DC-8F74-C08D1F03B457",
                "NOMBRE" => "USCHAKOV PAVEL",
                "TIEMPO" => Carbon::now("Europe/Madrid")->format("Y-m-d h:i:s")
            ],
            "5014124017" => [
                "ESTADO" => "OK",
                "ERROR" => "0",
                "DNI" => "C08D1F03B457-0A59-45DC-8F74-D97C44B3",
                "NOMBRE" => "LAGUNA RUIZ, NIEVES",
                "TIEMPO" => Carbon::now("Europe/Madrid")->format("Y-m-d h:i:s")
            ],
            "5014125387" => [
                "ESTADO" => "OK",
                "ERROR" => "0",
                "DNI" => "0A59-45DC-C08D1F03B457-8F74-D97C44B3",
                "NOMBRE" => "JORGE MASSANA DIEGUEZ",
                "TIEMPO" => Carbon::now("Europe/Madrid")->format("Y-m-d h:i:s")
            ],
            "3014125387" => [
                "ESTADO" => "OK",
                "ERROR" => "0",
                "DNI" => "D97C44B3",
                "NOMBRE" => "JAUME REI",
                "TIEMPO" => Carbon::now("Europe/Madrid")->format("Y-m-d h:i:s")
            ], 
            // "5014125387" => [
            //     "ESTADO" => "KO",
            //     "ERROR" => "INFORMACION NO LOCALIZADA",
            //     "DNI" => "",
            //     "NOMBRE" => "",
            //     "TIEMPO" => ""
            // ]
        ];

        $entity = new \stdClass();

        if (array_key_exists($id, $response)) {
            $entity->ESTADO = $response[$id]["ESTADO"];
            $entity->ERROR =  $response[$id]["ERROR"];
            $entity->DNI = $response[$id]["DNI"];
            $entity->NOMBRE =  $response[$id]["NOMBRE"];
            $entity->TIEMPO =  $response[$id]["TIEMPO"];
        } else {
            $entity->ESTADO = "KO";
            $entity->ERROR = "INFORMACION NO LOCALIZADA";
            $entity->DNI = "";
            $entity->NOMBRE = "";
            $entity->TIEMPO = "";
        }

        return $entity;
    }
}
