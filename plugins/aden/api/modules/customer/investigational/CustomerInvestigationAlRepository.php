<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InvestigationAl;

use DB;
use Exception;
use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Wgroup\SystemParameter\SystemParameter;

class CustomerInvestigationAlRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerInvestigationAlModel());

        $this->service = new CustomerInvestigationAlService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo de Documento", "name" => "customerDocumentNumber"],
            ["alias" => "Razón social", "name" => "customerBusinessName"],
            ["alias" => "Director gestión de riesgo", "name" => "directorName"],
            ["alias" => "Asesor gestión de riesgo", "name" => "agentName"],
            ["alias" => "Nro Id Trabajador", "name" => "employeeDocumentNumber"],
            ["alias" => "Nombre del trabajador", "name" => "employeeName"],
            ["alias" => "Fecha del accidente", "name" => "accidentDateOf"],
            ["alias" => "Fecha radicación IA empresa", "name" => "date_ia_customer"],
            ["alias" => "Fecha generación carta de recomendaciones", "name" => "date_letter_recommendation"],
            ["alias" => "Fecha de seguimiento", "name" => "dateOf"],
            ["alias" => "Estado de cumplimiento de la medida", "name" => "status"],
            ["alias" => "Causa de no cumplimiento", "name" => "comment"],
            ["alias" => "Solicitud Sisalud", "name" => "sisalud"],
            ["alias" => "Ciudad accidente", "name" => "accidentCity"],
            ["alias" => "Departamento accidente", "name" => "accidentState"],
            ["alias" => "Dirección empresa", "name" => "customerPrincipalAddress"],
            ["alias" => "Municipio empresa", "name" => "customerPrincipalCity"],
            ["alias" => "Departamento empresa", "name" => "customerPrincipalSate"],
            ["alias" => "Días falta seguimiento", "name" => "daysOf"],
        ];
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
            "status" => "wg_customer_investigation_al.status",
            "id" => "wg_customer_investigation_al.id",
            "sisalud" => "wg_customer_investigation_al.sisalud",
            "accidentDate" => "wg_customer_investigation_al.accidentDate",
            "accidentType" => "investigation_accident_type.item AS accidentType",
            "businessName" => "wg_customers.businessName",
            "documentNumber" => "wg_customers.documentNumber",
            "fullName" => "wg_employee.fullName",
            "investigatorId" => "wg_customer_investigation_al.investigator_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();
        

        /* Example relation*/
        $query->join("wg_customers", function ($join) {
            $join->on('wg_customer_investigation_al.customer_id', '=', 'wg_customers.id');

        })->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_investigation_al.customer_employee_id', '=', 'wg_customer_employee.id');

        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc', 'wg_customer_document_type')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'wg_customer_document_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('investigation_accident_type', 'investigation_accident_type')), function ($join) {
            $join->on('wg_customer_investigation_al.accidentType', '=', 'investigation_accident_type.value');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'agentId') {
                        $query->where(function ($query) use ($item) {                            
                            $query->where("wg_customer_investigation_al.agent_id", SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item))
                                ->orWhere('wg_customer_investigation_al.director_id', SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item));
                        });

                    } else {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        return $this->get($query, $criteria);
    }

    public function allTracking($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "customerDocumentNumber" => "c.documentNumber AS customerDocumentNumber",
            "customerBusinessName" => "c.businessName AS customerBusinessName",
            "directorName" => DB::raw("CONCAT_WS(' ', director.firstName, director.lastName) AS directorName"),
            "agentName" => DB::raw("CONCAT_WS(' ', agent.firstName, agent.lastName) AS agentName"),
            "employeeDocumentNumber" => "e.documentNumber AS employeeDocumentNumber",
            "employeeName" => "e.fullName AS employeeName",
            "accidentDateOf" => "wg_customer_investigation_al.accidentDateOf",
            "date_ia_customer" => "controlDates.date_ia_customer",
            "date_letter_recommendation" => "controlDates.date_letter_recommendation",
            "dateOf" => "measure.dateOf",
            "status" => "measure.status",
            "comment" => "measure.comment",
            "sisalud" => "wg_customer_investigation_al.sisalud",
            "accidentCity" => DB::raw("UPPER(tca.`name`) AS accidentCity"),
            "accidentState" => DB::raw("UPPER(usa.`name`) AS accidentState"),
            "customerPrincipalAddress" => "customerAddress.value AS customerPrincipalAddress",
            "customerPrincipalCity" => DB::raw("UPPER(tc.`name`) AS customerPrincipalCity"),
            "customerPrincipalSate" => DB::raw("UPPER(usc.`name`) AS customerPrincipalSate"),
            "daysOf" => DB::raw("0 AS daysOf"),
            "id" => "wg_customer_investigation_al.id",

        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();
        

        /* Example relation*/
        $query->join("wg_customers AS c", function ($join) {
            $join->on('wg_customer_investigation_al.customer_id', '=', 'c.id');

        })->join("wg_customer_employee AS ce", function ($join) {
            $join->on('wg_customer_investigation_al.customer_employee_id', '=', 'ce.id');

        })->join("wg_employee AS e", function ($join) {
            $join->on('ce.employee_id', '=', 'e.id');

        })->leftjoin('wg_towns AS tca', function ($join) {
            $join->on('tca.id', '=', 'wg_customer_investigation_al.accident_city_id');

        })->leftjoin('rainlab_user_states AS usa', function ($join) {
            $join->on('usa.id', '=', 'wg_customer_investigation_al.accident_state_id');

        })->leftjoin('wg_towns AS tc', function ($join) {
            $join->on('tc.id', '=', 'c.city_id');

        })->leftjoin('rainlab_user_states AS usc', function ($join) {
            $join->on('usc.id', '=', 'c.state_id');

        })->leftjoin(DB::raw('wg_agent AS director'), function ($join) {
            $join->on('wg_customer_investigation_al.director_id', '=', 'director.id');

        })->leftjoin(DB::raw('wg_agent AS agent'), function ($join) {
            $join->on('wg_customer_investigation_al.agent_id', '=', 'agent.id');

        })->leftjoin(DB::raw(CustomerInvestigationAlModel::getCustomerAddress('wg_info_detail', 'customerAddress')), function ($join) {
            $join->on('customerAddress.entityId', '=', 'c.id');

        })->leftjoin(DB::raw(CustomerInvestigationAlModel::getControlDates('wg_customer_investigation_al_control', 'controlDates')), function ($join) {
            $join->on('wg_customer_investigation_al.id', '=', 'controlDates.customer_investigation_id');

        })->leftjoin(DB::raw(CustomerInvestigationAlModel::getMeasure('wg_customer_investigation_al_measure', 'measure')), function ($join) {
            $join->on('wg_customer_investigation_al.id', '=', 'measure.customer_investigation_id');
        });

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

        $entityModel->customerContractDetailId = $entity->customerContractDetailId;
        $entityModel->comment = $entity->comment;

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
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }
}