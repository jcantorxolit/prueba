<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 4/26/2016
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\InformationDetail;


use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\System\Parameter\SystemParameter;
use Illuminate\Support\Facades\Log;
use DB;
use Exception;
use Carbon\Carbon;

class InformationDetailRepository extends BaseRepository
{
    public function __construct()
    {
        $this->makeModel(new InformationDetailModel());

        $this->setColumns([
            'id'                => 'aden_information_detail.id',
            'entity_id'         => 'aden_information_detail.entity_id',
            'entity_name'       => 'aden_information_detail.entity_name',
            'center'            => 'aden_information_detail.center',
            'type'              => 'aden_information_detail.type',
            'value'             => 'aden_document_type.value',
        ]);
    }

    public function all($criteria)
    {
        $this->parseCriteria($criteria);

        $query = $this->query();

        /*
        $query->join(DB::raw(SystemParameter::getRelationTable('aden_document_type')), function($join)
        {
            $join->on('aden_information_detail.document_type', '=', 'aden_document_type.value');
        });
        */

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $exc) {
                            Log::error($exc->getMessage());
                        }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();

        return $result;
    }

    public function insertOrUpdate($entityId, $entityName, $type, $entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $entityModel->entityId          = $entityId;
        $entityModel->entityName        = $entityName;
        $entityModel->type              = $type;
        $entityModel->value             = $entity->value;

        $entityModel->save();

        $result["result"] = true;

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

    public function bulkInsert($records, $entityName, $entityId)
    {
        try {
            foreach ($records as $record) {
                if ($record && $record->type != null) {
                    $isNewRecord = false;

                    if (!($entityModel = $this->find($record->id))) {
                        $entityModel = $this->model->newInstance();
                        $isNewRecord = true;
                    }

                    $entityModel->entityId          = $entityId;
                    $entityModel->entityName        = $entityName;
                    $entityModel->type              = $record->type->value;
                    $entityModel->value             = $record->value;

                    if ($isNewRecord) {
                        $entityModel->save();
                    } else {
                        $entityModel->save();
                    }
                }
            }
        }
        catch (Exception $ex) {

        }
    }

    public function findByEntity($entityName, $entityId)
    {
        $records = $this->model->where('entityName', $entityName)->where('entityId', $entityId)->get();

        $results = array();

        foreach ($records as $record) {
            //var_dump($record);
            $results[] = $this->parseModelWithRelations($record);
        }

        return $results;
    }

    public function findByType($type, $entityName, $entityId)
    {
        $record = $this->model->where('type' ,$type)->where('entityName', $entityName)->where('entityId', $entityId)->first();

        return $this->parseModelWithRelations($record);
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            //var_dump($model);
            $model->type = $model->getTypes();

            return $model;
        } else {
            return null;
        }
    }
}