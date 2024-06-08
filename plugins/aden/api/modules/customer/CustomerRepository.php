<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Event;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\Agent\CustomerAgentModel;
use AdeN\Api\Modules\Customer\Audit\CustomerAuditRepository;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeRepository;
use AdeN\Api\Modules\Customer\User\CustomerUserRepository;
use RainLab\User\Models\User;

class CustomerRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerModel());

        $this->service = new CustomerService();

        CustomerModel::updating(function ($model) {
            CustomerAuditRepository::createMatrix($model);
        });
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo de Documento", "name" => "documentType"],
            ["alias" => "Nro Documento", "name" => "documentNumber"],
            ["alias" => "Razón Social", "name" => "businessName"],
            ["alias" => "Tipo de Cliente", "name" => "type"],
            ["alias" => "Clasificación", "name" => "classification"],
            ["alias" => "Grupo Económico", "name" => "economicGroup"],
            ["alias" => "Estado", "name" => "status"],
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

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');
        })->whereRaw("wg_customers.isDeleted = 0");;

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allAgent($criteria)
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

        $authUser = $this->getAuthUser();

        $query = $this->query();

        $q1 = CustomerAgentModel::getRelationRaw();

        /* Example relation*/
        $query->join(DB::raw("({$q1->toSql()}) as wg_customer_agent"), function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_agent.customer_id');
        })->join("wg_agent", function ($join) {
            $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
        })->join("users", function ($join) {
            $join->on('users.id', '=', 'wg_agent.user_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');
        })
            ->where("users.id", $authUser ? $authUser->id : 0)
            ->whereRaw("wg_customers.isDeleted = 0");

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allContractor($criteria)
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

        $q1 = DB::table('wg_customers')
            ->select(
                'id',
                'documentType',
                'documentNumber',
                'businessName',
                'type',
                'classification',
                'status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentType',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                'wg_customers.type',
                'wg_customers.classification',
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0")
            ->whereRaw("wg_customers.classification = 'Contratista'");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_contractor.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $q1->union($q2);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customers")))
            ->mergeBindings($q1);

        /* Example relation*/
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
                    if ($item->field != 'customerId') {
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

    public function allEconomigGroup($criteria)
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

        $q1 = DB::table('wg_customers')
            ->select(
                'id',
                'documentType',
                'documentNumber',
                'businessName',
                'type',
                'classification',
                'status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentType',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                'wg_customers.type',
                'wg_customers.classification',
                'wg_customers.status'
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_economic_group.parent_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $q1->union($q2)->mergeBindings($q2);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customers")))
            ->mergeBindings($q1);

        /* Example relation*/
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
                    if ($item->field != 'customerId') {
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

    public function allContractorEconomicGroup($criteria)
    {
        $this->setColumns([
            "id" => "wg_customers.id",
            "documentType" => "tipodoc.item as documentType",
            "documentNumber" => "wg_customers.documentNumber",
            "businessName" => "wg_customers.businessName",
            "type" => "tipocliente.item AS type",
            "classification" => "customer_classification.item AS classification",
            "economicGroup" => DB::raw("MAX(economicGroup) AS economicGroup"),
            "status" => "estado.item AS status",
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customers')
            ->select(
                'id',
                'documentType',
                'documentNumber',
                'businessName',
                'type',
                'classification',
                'status',
                DB::raw('NULL AS economicGroup')
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentType',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                'wg_customers.type',
                'wg_customers.classification',
                'wg_customers.status',
                DB::raw('NULL AS economicGroup')
            )
            ->whereRaw("wg_customers.isDeleted = 0")
            ->whereRaw("wg_customers.classification = 'Contratista'");

        $q3 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.documentType',
                'wg_customers.documentNumber',
                'wg_customers.businessName',
                'wg_customers.type',
                'wg_customers.classification',
                'wg_customers.status',
                DB::raw("'Pertenece Grupo Economico' AS economicGroup")
            )
            ->whereRaw("wg_customers.isDeleted = 0");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->whereRaw(SqlHelper::getPreparedField('wg_customers.id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q2->whereRaw(SqlHelper::getPreparedField('wg_customer_contractor.customer_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        $q3->whereRaw(SqlHelper::getPreparedField('wg_customer_economic_group.parent_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                    }
                }
            }
        }

        $q1->union($q2)->union($q3)
            ->mergeBindings($q2)
            ->mergeBindings($q3);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customers")))
            ->mergeBindings($q1);

        /* Example relation*/
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_customers.documentType', '=', 'tipodoc.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente')), function ($join) {
            $join->on('wg_customers.type', '=', 'tipocliente.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_customers.status', '=', 'estado.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification')), function ($join) {
            $join->on('wg_customers.classification', '=', 'customer_classification.value');
        })->groupBy('wg_customers.id');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field != 'customerId') {
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

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->businessName = $entity->businessName;
        $entityModel->economicActivity = $entity->economicActivity ? $entity->economicActivity->id : null;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->documentType = $entity->documentType ? $entity->documentType->value : null;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->webSite = $entity->webSite;
        $entityModel->arl = $entity->arl ? $entity->arl->value : null;
        $entityModel->country_id = $entity->country ? $entity->country->id : null;
        $entityModel->state_id = $entity->state ? $entity->state->id : null;
        $entityModel->city_id = $entity->city ? $entity->city->id : null;
        $entityModel->directEmployees = $entity->directEmployees;
        $entityModel->totalEmployee = $entity->totalEmployee ? $entity->totalEmployee->value : null;
        $entityModel->riskClass = $entity->riskClass ? $entity->riskClass->value : null;
        $entityModel->riskLevel = $entity->riskLevel ? $entity->riskLevel->value : null;

        if ($isNewRecord) {
            $entityModel->classification = "No Cliente";
            $entityModel->status = 1;
            $entityModel->isDeleted = 0;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function updateMatrix($entity)
    {
        if (($entityModel = $this->find($entity->id))) {
            $authUser = $this->getAuthUser();
            $entityModel->matrixType = $entity->matrixType;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();

            if ($entityModel->matrixType == 'E') {
                Event::fire('migrate.express', array($entityModel));
            } else {
                $data = new \stdClass();
                $data->customerId = $entityModel->id;
                $data->updatedBy = $entityModel->updatedBy;
                Event::fire('migrate.gtc45', array($data));
            }

            return $this->parseModelWithRelations($entityModel);
        }
    }

    public function updateEconomicActivity($entity)
    {
        if (($entityModel = $this->find($entity->id))) {
            $authUser = $this->getAuthUser();
            $entityModel->economicActivity = $entity->economicActivityId;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
            return $this->parseModelWithRelations($entityModel);
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function canSignUp($entity)
    {
        $message = '';

        try {
            $userModel = User::findByEmail($entity->user->email);

            if ($userModel) {
                throw new Exception("El usuario que desea crear ya existe.");
            }

            $criteria = new \stdClass();
            $criteria->customerId = 0;
            $criteria->employeeDocumentType = $entity->user->documentType ? $entity->user->documentType->value : null;
            $criteria->employeeDocumentNumber = $entity->user->documentNumber;

            if (isset($entity->customer->id) && $entity->customer->id > 0) {

                $criteria->customerId = $entity->customer->id;
                $employeeInCustomer = (new CustomerEmployeeRepository)->findInCustomer($criteria);

                if (!$employeeInCustomer) {
                    throw new Exception("El usuario que desea crear no es empleado de la empresa.");
                }

                if ($employeeInCustomer->userId != null &&  $employeeInCustomer->userEmail != null && ($employeeInCustomer->userEmail != trim($entity->user->email))) {
                    throw new Exception("El usuario que desea crear ya existe en la empresa con otro e-mail registrado.");
                }

                // $employeeNotInCustomer = (new CustomerEmployeeRepository)->findInDifferentCustomer($criteria);

                // if ($employeeNotInCustomer) {
                //     throw new Exception("El usuario que desea crear ya existe en otra empresa.");
                // }
            } else {
                // $employee = (new CustomerEmployeeRepository)->findByDocument($criteria);

                // if ($employee) {
                //     throw new Exception("La identificación del usuario ya existe.");
                // }
            }

            if ((new CustomerUserRepository)->hasCustomerAdmin($criteria)) {
                throw new \Exception("Ya existe un usuario Cliente Admin en la empresa.");
            }
        } catch (\Exception $ex) {
            \Log::error($ex);
            $message = $ex->getMessage();
        }

        return [
            "allowed" => empty($message),
            "message" => $message
        ];
    }

    public function signUp($entity)
    {
        DB::transaction(function () use ($entity) {
            //CREATE CUSTOMER
            $customer = $entity->customer;

            if (!isset($customer->id) || $customer->id == 0) {
                $entity->customer = $this->createFromSignUp($customer);
            }

            //CREATE EMPLOYEE
            //CustomerEmployeeRepository::createFromSignUp($entity);

            //CREATE CUSTOMER USER
            return CustomerUserRepository::createFromSignUp($entity);
        });
    }

    public function createFromSignUp($entity)
    {
        $newEntity = new \stdClass();
        $newEntity->id = 0;
        $newEntity->businessName = $entity->businessName;
        $newEntity->economicActivity = $entity->economicActivity;
        $newEntity->type = $entity->type;
        $newEntity->documentType = $entity->documentType;
        $newEntity->documentNumber = $entity->documentNumber;
        $newEntity->webSite = $entity->webSite;
        $newEntity->arl = $entity->arl;
        $newEntity->country = $entity->country;
        $newEntity->state = $entity->state;
        $newEntity->city = $entity->city;
        $newEntity->directEmployees = $entity->directEmployeeNumber;
        $newEntity->totalEmployee = $entity->totalEmployee;
        $newEntity->riskClass = $entity->riskClass;
        $newEntity->riskLevel = $entity->riskClass ? $this->getRiskLevel($entity->riskClass->value) : null;

        return $this->insertOrUpdate($newEntity);
    }

    private function getRiskLevel($value)
    {
        $riskLevel = new \stdClass();
        $riskLevel->value = intval($value) > 3 ? '45' : '123';
        return $riskLevel;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->businessName = $model->businessName;
            $entity->documentNumber = $model->documentNumber;
            $entity->matrixType = $model->matrixType;

            return $entity;
        } else {
            return null;
        }
    }

    public function findByDocument($criteria)
    {
        return $this->service->findByDocument($criteria);
    }

    public function getDocumentTypeList($customerId)
    {
        return $this->service->getDocumentTypeList($customerId);
    }

    public function getEmployeeDocumentTypeList($customerId)
    {
        return $this->service->getEmployeeDocumentTypeList($customerId);
    }

    public function getWorkplaceList($customerId)
    {
        return $this->service->getWorkplaceList($customerId);
    }

    public function getMacroprocessList($criteria)
    {
        return $this->service->getMacroprocessList($criteria);
    }

    public function getProcessList($criteria)
    {
        return $this->service->getProcessList($criteria);
    }

    public function getJobList($customerId)
    {
        return $this->service->getJobList($customerId);
    }

    public function getActivityList($customerId)
    {
        return $this->service->getActivityList($customerId);
    }

    public function getHasEconomicGroupList($criteria)
    {
        return $this->service->getHasEconomicGroupList($criteria);
    }

    public function getEmployeerList($criteria)
    {
        return $this->service->getEmployeerList($criteria);
    }

    public function getRelatedAgentAndUserList($customerId)
    {
        return $this->service->getRelatedAgentAndUserList($customerId);
    }

    public function getSupportHelpInformation($user)
    {
        $customer = $this->find($user->company);
        return $this->service->getSupportHelpInformation($customer, $user);
    }


    public function getAmountEmployeesAll($customerId)
    {
        return $this->service->getAmountEmployeesAll($customerId);
    }

    public function getAmountEmployeesChartStackedBar($customerId)
    {
        return $this->service->getAmountEmployeesChartStackedBar($customerId);
    }
}
