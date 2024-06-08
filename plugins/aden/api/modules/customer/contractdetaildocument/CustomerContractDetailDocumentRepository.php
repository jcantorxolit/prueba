<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ContractDetailDocument;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\Customer\CustomerModel;
use Wgroup\SystemParameter\SystemParameter;

class CustomerContractDetailDocumentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerContractDetailDocumentModel());

        $this->service = new CustomerContractDetailDocumentService();
    }

    public static function getCustomFilters()
    {
        return [];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_contract_detail_document.id",
            "type" => "contract_detail_document_type.item AS documentType",
            "description" => "wg_customer_contract_detail_document.description",
            "version" => "wg_customer_contract_detail_document.version",
            "createdAt" => "wg_customer_contract_detail_document.created_at",
            "status" => "customer_document_status.item AS status",
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
            "customerContractDetailId" => "wg_customer_contract_detail_document.customer_contract_detail_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join('wg_customer_contract_detail', function ($join) {
            $join->on('wg_customer_contract_detail.id', '=', 'wg_customer_contract_detail_document.customer_contract_detail_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable("contract_detail_document_type")), function ($join) {
            $join->on('wg_customer_contract_detail_document.type', '=', 'contract_detail_document_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable("customer_document_status")), function ($join) {
            $join->on('wg_customer_contract_detail_document.status', '=', 'customer_document_status.value');

        })/*->leftjoin(DB::raw(CustomerContractDetailDocumentModel::getSystemFile()), function ($join) {
            $join->on('wg_customer_contract_detail_document.id', '=', 'system_files.attachment_id');

        })*/->leftjoin('users', function ($join) {
            $join->on('users.id', '=', 'wg_customer_contract_detail_document.created_by');

        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerContractDetailDocumentModel::CLASS_NAME);
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

        $entityModel->customerContractDetailId = $entity->customerContractDetailId;
        $entityModel->type = $entity->type ? $entity->type->value : null;;
        $entityModel->description = $entity->description;
        $entityModel->version = $entity->version;
        $entityModel->status = $entity->status ? $entity->status->value : null;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
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

            return $entity;
        } else {
            return null;
        }
    }
}
