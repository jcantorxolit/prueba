<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Agent;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Wgroup\SystemParameter\SystemParameter;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use stdClass;

class CustomerAgentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerAgentModel());

        $this->service = new CustomerAgentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_agent.id",
            "documentType" => "tipodoc.item as documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "type" => "bunit.item AS type",
            "status" => "estado.item AS status",
            "agentId" => "wg_customer_agent.agent_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation */
        $query->join("wg_agent", function ($join) {
            $join->on('wg_customer_agent.agent_id', '=', 'wg_agent.id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_agent.customer_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('bunit')), function ($join) {
            $join->on('wg_customer_agent.type', '=', 'bunit.value');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customers.id",
            "documentType" => "tipodoc.item as documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "type" => "tipocliente.item AS type",
            "classification" => "customer_classification.item AS classification",
            "status" => "estado.item AS status",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customers'));

        /* Example relation */
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "notInRaw") {
                        $query->whereNotIn('wg_customers.id', function ($query) use ($item) {
                            $query->select('customer_id')
                                ->from('wg_customer_agent')
                                ->where('wg_customer_agent.agent_id', '=', SqlHelper::getPreparedData($item));
                        });
                    }
                }
            }
        }

        $this->applyCriteria($query, $criteria, ['agentId']);

        $data = $this->get($query, $criteria);

        $data['uids'] = $this->allAvailableUids($criteria);

        return $data;
    }

    public function allAvailableUids($criteria)
    {
        $this->clearColumns();
        $this->setColumns([
            "id" => "wg_customers.id",
            "documentType" => "tipodoc.item as documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "type" => "tipocliente.item AS type",
            "classification" => "customer_classification.item AS classification",
            "status" => "estado.item AS status",
        ]);

        $this->parseCriteria(null);

        $query = $this->query(DB::table('wg_customers'));

        /* Example relation */
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "notInRaw") {
                        $query->whereNotIn('wg_customers.id', function ($query) use ($item) {
                            $query->select('customer_id')
                                ->from('wg_customer_agent')
                                ->where('wg_customer_agent.agent_id', '=', SqlHelper::getPreparedData($item));
                        });
                    }
                }
            }
        }

        $this->applyCriteria($query, $criteria, ['agentId']);

        $data = $this->get($query, $criteria);

        $result = array_values(array_map(function ($row) {
            return $row->id;
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

        $entityModel->customerId = $entity->customerId ? $entity->customerId->id : null;
        $entityModel->agentId = $entity->agentId ? $entity->agentId->id : null;
        $entityModel->type = $entity->type ? $entity->type->value : null;


        if ($isNewRecord) {
            $entityModel->save();
        } else {
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function bulkInsertOrUpdate($entity)
    {
        foreach ($entity->customerUIds as $customer) {
            $data = new \stdClass;
            $data->id = 0;
            $data->customerId = new stdClass;
            $data->customerId->id = $customer;
            $data->agentId = new stdClass;
            $data->agentId->id = $entity->agentId;
            $data->type = $entity->type;
            $this->insertOrUpdate($data);
        }

        return $entity;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
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
            $entity->customerId = new stdClass;
            $entity->customerId->id = $model->customerId;
            $entity->agentId = new stdClass;
            $entity->agentId->id = $model->agentId;
            $entity->type = $model->getType();

            return $entity;
        } else {
            return null;
        }
    }
}
