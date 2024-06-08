<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\UnsafeAct;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use Carbon\Carbon;
use Exception;
use Wgroup\SystemParameter\SystemParameter;
use DB;
use Log;
use Illuminate\Support\Collection;

class CustomerUnsafeActRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerUnsafeActModel());

        $this->service = new CustomerUnsafeActService();
    }

    public static function getCustomMassiveFilters()
    {
        return [
            ["alias" => "Fecha", "name" => "dateof"],
            ["alias" => "Centro de Trabajo", "name" => "workPlace"],
            ["alias" => "Tipo de Peligro", "name" => "riskType"],
            ["alias" => "Descripción de la Condición Insegura", "name" => "description"]
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_unsafe_act.id",
            "dateof" => "wg_customer_unsafe_act.dateOf",
            "workPlace" => "wg_customer_config_workplace.name AS work_place",
            "riskType" => "wg_config_job_activity_hazard_classification.name AS risk_type",
            "place" => "wg_customer_unsafe_act.place",
            "description" => "wg_customer_unsafe_act.description",
            "assignedTo" => "responsible_unsafe_act.name AS assignedTo",
            "reportedBy" => DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS reportedBy"),
            "status" => "customer_unsafe_act_status.item AS status",
            "statusCode" => "wg_customer_unsafe_act.status AS statusCode",
            "hasImages" => DB::raw("CASE WHEN wg_customer_unsafe_act.imageUrl IS NULL OR wg_customer_unsafe_act.imageUrl = '' THEN 0 ELSE 1 END hasImage"),
            "customerId" => "wg_customer_unsafe_act.customer_id",
            "assignedToId" => "responsible_unsafe_act.user_id AS assignedToId",
            "reportedById" => "users.id AS reportedById",
        ]);

        $this->parseCriteria($criteria);

        $qAgentUser = CustomerModel::getRelatedUnsafeActAgentAndUserRaw($criteria);

        $query = $this->query();

        /* Example relation */
        $query->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
            $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_unsafe_act"), function ($join) {
            $join->on('wg_customer_unsafe_act.responsible_id', '=', 'responsible_unsafe_act.id');
            $join->on('wg_customer_unsafe_act.responsible_type', '=', 'responsible_unsafe_act.type');
            $join->on('wg_customer_unsafe_act.customer_id', '=', 'responsible_unsafe_act.customer_id');
        })->mergeBindings($qAgentUser)
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_unsafe_act.createdBy');
            });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allMassive($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_unsafe_act.id",
            "dateof" => "wg_customer_unsafe_act.dateOf",
            "workPlace" => "wg_customer_config_workplace.name AS work_place",
            "riskType" => "wg_config_job_activity_hazard_classification.name AS risk_type",
            "description" => "wg_customer_unsafe_act.description",
            "status" => "customer_unsafe_act_status.item AS status",
            "customerId" => "wg_customer_unsafe_act.customer_id",
            "assignedToId" => "responsible_unsafe_act.user_id AS assignedToId",
            "reportedById" => "users.id AS reportedById",
        ]);

        $this->parseCriteria($criteria);

        $qAgentUser = CustomerModel::getRelatedUnsafeActAgentAndUserRaw($criteria);

        $query = $this->query();

        /* Example relation */
        $query->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
            $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_unsafe_act"), function ($join) {
            $join->on('wg_customer_unsafe_act.responsible_id', '=', 'responsible_unsafe_act.id');
            $join->on('wg_customer_unsafe_act.responsible_type', '=', 'responsible_unsafe_act.type');
            $join->on('wg_customer_unsafe_act.customer_id', '=', 'responsible_unsafe_act.customer_id');
        })
            ->mergeBindings($qAgentUser)
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_unsafe_act.createdBy');
            })
            ->whereRaw("wg_customer_unsafe_act.imageUrl IS NOT NULL AND wg_customer_unsafe_act.imageUrl <> ''");

        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $data['uids'] = $this->allUids($criteria);

        return $data;
    }

    public function allUids($criteria)
    {
        $this->clearColumns();
        $this->setColumns([
            "id" => "wg_customer_unsafe_act.id",
            "customerId" => "wg_customer_unsafe_act.customer_id",
            "assignedToId" => "responsible_unsafe_act.user_id AS assignedToId",
            "reportedById" => "users.id AS reportedById",
        ]);

        $this->parseCriteria(null);

        $qAgentUser = CustomerModel::getRelatedUnsafeActAgentAndUserRaw($criteria);

        $query = $this->query();

        /* Example relation */
        $query->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
            $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_unsafe_act"), function ($join) {
            $join->on('wg_customer_unsafe_act.responsible_id', '=', 'responsible_unsafe_act.id');
            $join->on('wg_customer_unsafe_act.responsible_type', '=', 'responsible_unsafe_act.type');
            $join->on('wg_customer_unsafe_act.customer_id', '=', 'responsible_unsafe_act.customer_id');
        })
            ->mergeBindings($qAgentUser)
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_unsafe_act.createdBy');
            })
            ->whereRaw("wg_customer_unsafe_act.imageUrl IS NOT NULL AND wg_customer_unsafe_act.imageUrl <> ''");;

        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $result = array_values(array_map(function ($row) {
            return $row['id'];
        }, $data['data']));

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

        $entityModel->customerId = $entity->customerId;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->dateof = $entity->dateof ? Carbon::parse($entity->dateof)->timezone('America/Bogota') : null;
        $entityModel->workPlace = $entity->workPlace ? $entity->workPlace->value : null;
        $entityModel->riskType = $entity->riskType ? $entity->riskType->value : null;
        $entityModel->classificationId = $entity->classificationId ? $entity->classificationId->value : null;
        $entityModel->place = $entity->place;
        $entityModel->lat = $entity->lat ? $entity->lat->value : null;
        $entityModel->lng = $entity->lng ? $entity->lng->value : null;
        $entityModel->description = $entity->description;
        $entityModel->origin = $entity->origin;
        $entityModel->imageurl = $entity->imageurl;
        $entityModel->addressFormatted = $entity->addressFormatted;
        $entityModel->responsibleType = $entity->responsibleType;
        $entityModel->responsibleId = $entity->responsibleId;
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
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->status = $model->getStatus();
            $entity->dateof = $model->dateof;
            $entity->workPlace = $model->getWorkPlace();
            $entity->riskType = $model->getRiskType();
            $entity->classificationId = $model->getClassificationId();
            $entity->place = $model->place;
            $entity->lat = $model->getLat();
            $entity->lng = $model->getLng();
            $entity->description = $model->description;
            $entity->origin = $model->origin;
            $entity->imageurl = $model->imageurl;
            $entity->addressFormatted = $model->addressFormatted;
            $entity->responsibleType = $model->responsibleType;
            $entity->responsibleId = $model->responsibleId;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;

            return $entity;
        } else {
            return null;
        }
    }

    public function migrateFilesApi()
    {
        return $this->service->migrateFilesApi();
    }

    public function getYearList($criteria)
    {
        return $this->service->getYearList($criteria);
    }

    public function getWokplaceList($criteria)
    {
        return $this->service->getWokplaceList($criteria);
    }

    public function getChartWorkplace($criteria)
    {
        return $this->service->getChartWorkplace($criteria);
    }

    public function getChartHazard($criteria)
    {
        return $this->service->getChartHazard($criteria);
    }

    public function getChartPeriod($criteria)
    {
        return $this->service->getChartPeriod($criteria);
    }

    public function getChartStatus($criteria)
    {
        return $this->service->getChartStatus($criteria);
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportExcelData($criteria);
        $filename = 'Condiciones_Inseguras' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Condiciones Inseguras', $data);
    }

    public function exportReport($criteria)
    {
        $data = $this->service->getExportReportData($criteria);
        $filename = 'Informe_Condicones_Inseguras' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Informe', $data);
    }

    public function exportZip($criteria)
    {
        //$entity = $this->find($criteria->id);
        $entity = \Wgroup\CustomerUnsafeAct\CustomerUnsafeAct::find($criteria->id);

        $filename = 'Evidencias_Condicones_Inseguras_' . Carbon::now()->timestamp . '.zip';

        $data = [];

        if ($entity->imageUrl != "") {
            $images = json_decode($entity->imageUrl);
            if ($images) {
                $photos = $entity->photos;
                $data = array_map(function ($image) use ($photos) {

                    if (isset($image->id)) {
                        $photo = $this->getImage($photos, $image->id);

                        if ($photo != null) {
                            $image->url = $photo->getTemporaryUrl();
                            $image->filename = $photo->getDiskPath();
                        }
                    }

                    $image->filename = isset($image->filename) ? $image->filename : $image->url;

                    return [
                        "fullPath" => $image->url,
                        "filename" => $image->filename,
                    ];
                }, $images);
            }
        }

        ExportHelper::zipDownload($filename, $data);
    }

    private function getImage($photos, $fileId)
    {
        foreach ($photos as $photo) {
            if ($fileId == $photo->id) {
                return $photo;
            }
        }
        return null;
    }

    public function exportMassiveZip($criteria)
    {

        $data = [];

        $entities = $this->service->getExportZipData($criteria);

        foreach ($entities["data"] as $entity) {
            if ($entity->imageUrl != "") {
                $images = json_decode($entity->imageUrl);
                if ($images) {
                    $newEntity = \Wgroup\CustomerUnsafeAct\CustomerUnsafeAct::find($entity->id);
                    $photos = $newEntity->photos;

                    $data = array_merge($data, array_map(function ($image) use ($entity, $photos) {

                        if (isset($image->id)) {
                            $photo = $this->getImage($photos, $image->id);

                            if ($photo != null) {
                                $image->url = $photo->getTemporaryUrl();
                                $image->filename = $photo->getDiskPath();
                            }
                        }

                        $image->filename = isset($image->filename) ? $image->filename : $image->url;

                        $item = new \stdClass();
                        $item->fullPath = $image->url;
                        $item->filename = $image->filename;
                        $item->id = $entity->id;
                        $item->status = $entity->status;
                        $item->dateOf = $entity->dateOf;
                        $item->description = $entity->description;
                        $item->risk_type = $entity->risk_type;
                        $item->assignedTo = $entity->assignedTo;
                        $item->reportedBy = $entity->reportedBy;
                        $item->work_place = $entity->work_place;
                        $item->place = $entity->place;
                        $item->address_formatted = $entity->address_formatted;
                        return $item;
                    }, $images));
                }
            }
        }

        $collection = new Collection($data);

        $folders = $collection->groupBy('id');

        $result = $folders->map(function ($items, $key) use ($entities, $collection) {
            $folder = [
                "isDir" => true,
                "name" => "Condición_Insegura_" . $key,
                "items" => array_map(function ($item) {
                    return [
                        "fullPath" => $item->fullPath,
                        "filename" => $item->filename,
                    ];
                }, $items->toArray())
            ];

            $row = $collection->filter(function ($item) use ($key) {
                return $item->id == $key;
            })->first();

            $fileContents = $this->getCsvData($entities['heading'], $row);

            $folder['items'][] = [
                "fullPath" => null,
                "filename" => "Condición_Insegura_" . $key . ".csv",
                "fileContents" => $fileContents
            ];

            return $folder;
        });

        $filename = 'Evidencias_Masivas_Condicones_Inseguras_' . Carbon::now()->timestamp . '.zip';
        ExportHelper::zipDownload($filename, $result);
    }

    private function getCsvData($heading, $row)
    {
        $fields = [
            $heading,
            [
                ($row->status),
                ($row->dateOf),
                ($row->description),
                ($row->risk_type),
                ($row->assignedTo),
                ($row->reportedBy),
                ($row->work_place),
                ($row->place),
                ($row->address_formatted)
            ]
        ];

        return CmsHelper::csvToStr($fields);
    }

    public function getCountUnsafeConditions($criteria)
    {
        return $this->service->getCountUnsafeConditions($criteria);
    }
}
