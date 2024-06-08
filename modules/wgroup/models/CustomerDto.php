<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use AdeN\Api\Modules\Productivity\CustomerProductivityMatrix\CustomerProductivityMatrixRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use Wgroup\Models\Customer;
use DB;
use Carbon\Carbon;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class CustomerDto
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getInfoBasic($model);
        }
    }

    private function getInfoBasic($model)
    {

        //var_dump($model);
        //Codigo
        $this->id = $model->id;

        // Razon Social
        $this->businessName = $model->businessName;

        // Actividad Economic
        $this->economicActivity = $model->getEconomicActivity();

        $this->economicActivity = $this->economicActivity ? $this->economicActivity : $model->economicActivity;

        // Estado
        $this->status = $model->getStatus();

        // Tipo de Cliente
        $this->type = $model->getType();

        //Tipo de Documento
        $this->documentType = $model->getDocumentType();

        $this->classification = $model->getClassification();

        //Numero de Documento
        $this->documentNumber = $model->documentNumber;

        // Sitio Web
        $this->webSite = $model->webSite;

        // Arl
        $this->arl = $model->getArl();
        $this->size = $model->getSize();

        // Pais
        $this->country = $model->country;

        // Departamento
        $this->state = $model->state;

        // Municipio
        $this->town = $model->town;

        // Empleados Directos
        $this->directEmployees = $model->directEmployees;

        // Empleados Temporales
        $this->temporaryEmployees = $model->temporaryEmployees;

        // Empleados Temporales
        $this->temporalCompany = $model->temporalCompanyName;

        // Empleados Temporales
        $this->number = $model->temporalCompanyName;

        // Contract
        $this->contractNumber = $model->contractNumber != '0' ? $model->contractNumber : null;
        $this->contractStartDate = $model->contractStartDate ? Carbon::parse($model->contractStartDate)->format("d/m/Y") : null;
        $this->contractEndDate = $model->contractEndDate ? Carbon::parse($model->contractEndDate)->format("d/m/Y") : null;

        $this->totalEmployee = $model->getTotalEmployee();
        $this->riskLevel = $model->getRiskLevel();
        $this->riskClass = $model->getRiskClass();

        $this->group = $model->group;

        if ($model->maincontacts()->count()) {
            $this->maincontacts = ContactDto::parse($model->maincontacts);
        } else {
            $this->maincontacts = [];
        }

        $this->contacts = InfoDetailDto::parse($model->infoDetail());

        if ($model->unities()->count()) {
            $this->unities = CustomerAgentDto::parse($model->getUnities(), $this->id);
        } else {
            $this->unities = [];
        }

        $this->projectTypes = $model->getProjectTypes();

        $this->userSkills = $model->getUserSkills();

        $this->employeeDocumentsType = $model->getEmployeeDocumentTypes();

        $this->documentsType = $model->getCustomerDocumentTypes();

        $this->contactTypes = $model->getContactTypes();

        $this->employeeDocumentsTypeList = $model->getEmployeeDocumentTypesList();

        $this->contactTypeList = $model->getContactTypeList();

        $this->documentsTypeList = $model->getCustomerDocumentTypesList();

        $this->extraContactInformation = $model->getExtraContactInformation();

        $this->extraContactInformationList = $model->getExtraContactInformationList();

        $this->assignedHours = $model->getEconomicGroupAssignedHours();

        $this->projectTaskTypes = $model->getProjectTaskTypes();

        $this->contractorTypeList = $model->getContractorTypes();

        $this->userNotificationList = $model->usersNotification();
        $this->experienceVRList = $model->experienceVR();

        $this->officeTypeMatrixSpecialList = $model->getOfficeTypeMatrixSpecialList();

        $this->businessUnitMatrixSpecialList = $model->getBusinessUnitMatrixSpecialList();

        $this->resource = $model->getResource();
        $this->plans = $model->getPlans();

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at ? $model->updated_at->format('d/m/Y') : null;
        $this->tokensession = $this->getTokenSession(true);
        $this->logo = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);
        $this->hasEconomicGroup = $model->hasEconomicGroup == 1 ? true : false;

        $this->address = $model->getInfoDetailTable('dir');
        $this->phone = $model->getInfoDetailTable('tel');

        $this->matrixType = $model->matrixType;

        $this->covidBolivarRegister = false;
        $this->covidBolivarRegisterCandEdit = false;
        $this->attentionLines = $model->attentionLines();

        $this->productivityMatrix = '';
    }

    public function getInfoBasicEmployeeDocumentType($model)
    {
        $this->id = $model->id;

        // Razon Social
        $this->businessName = $model->businessName;

        //Numero de Documento
        $this->documentNumber = $model->documentNumber;

        // Sitio Web
        $this->webSite = $model->webSite;

        // Pais
        $this->country = $model->country;

        // Departamento
        $this->state = $model->state;

        // Municipio
        $this->town = $model->town;

        // Empleados Directos
        $this->directEmployees = $model->directEmployees;

        // Empleados Temporales
        $this->temporaryEmployees = $model->temporaryEmployees;

        // Empleados Temporales
        $this->temporalCompany = $model->temporalCompanyName;

        // Empleados Temporales
        $this->number = $model->temporalCompanyName;

        // Contract
        $this->contractNumber = $model->contractNumber != '0' ? $model->contractNumber : null;
        $this->contractStartDate = $model->contractStartDate ? Carbon::parse($model->contractStartDate)->format("d/m/Y") : null;
        $this->contractEndDate = $model->contractEndDate ? Carbon::parse($model->contractEndDate)->format("d/m/Y") : null;

        $this->group = $model->group;

        $this->employeeDocumentsType = $model->getEmployeeDocumentTypes();

        $this->documentsType = $model->getCustomerDocumentTypes();

        $this->employeeDocumentsTypeList = $model->getEmployeeDocumentTypesList();

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at ? $model->updated_at->format('d/m/Y') : null;
        $this->tokensession = $this->getTokenSession(true);
        $this->hasEconomicGroup = $model->hasEconomicGroup == 1 ? true : false;

        $this->matrixType = $model->matrixType;

        $this->covidBolivarRegister = false;
        $this->covidBolivarRegisterCandEdit = false;

        $this->productivityMatrix = '';

        return $this;
    }

    private function getInfoBasicUnit($model)
    {
        //var_dump($model);
        //Codigo
        $this->id = $model->id;

        if ($model->unities()->count()) {
            $this->unities = CustomerAgentDto::parse($model->getUnities(), $this->id);
        } else {
            $this->unities = [];
        }
    }

    private function getInfoBasicShort($model)
    {
        $this->arl = $model->getArl();
        $this->id = $model->id;
        $this->item = $model->businessName;
        $this->value = $model->id;
    }

    private function getInfoBasicContractor($model)
    {
        $this->id = $model->id;
        $this->businessName = $model->businessName;
        $this->documentNumber = $model->documentNumber;
    }

    private function getReportSummaryPrg($model)
    {
        //Log::info($model);
        $this->labels = $model["labels"];
        $this->datasets = $model["datasets"];
    }

    private function getReportYear($model)
    {
        $this->id = $model->id;
        $this->item = $model->item;
        $this->value = $model->value;
    }

    public static function fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // Razon Social
        $model->businessName = $object->businessName;

        // Actividad Economic
        $model->economicActivity = null;

        if (isset($object->economicActivity)) {
            $model->economicActivity = is_object($object->economicActivity) && $object->economicActivity ? $object->economicActivity->id : $object->economicActivity;
        }

        // Estado
        $model->status = $object->status ? $object->status->value : null;

        // Tipo de Cliente
        $model->type = $object->type ? $object->type->value : null;

        //Tipo de Documento
        $model->documentType = $object->documentType ? $object->documentType->value : null;

        $model->classification = $object->classification ? $object->classification->value : null;

        //Numero de Documento
        $model->documentNumber = $object->documentNumber;

        // Sitio Web
        $model->webSite = isset($object->webSite) ? $object->webSite : null;

        // Arl
        $model->arl = $object->arl ? $object->arl->value : null;

        // Pais
        $model->country_id = !isset($object->country->id) ? null : $object->country->id;

        // Departamento
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;

        // Municipio
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;

        // Empleados Directos
        $model->directEmployees = $object->directEmployees;

        // Empleados Temporales
        $model->temporaryEmployees = $object->temporaryEmployees;

        // Empleados Temporales
        $model->temporalCompanyName = $object->temporalCompany;

        // Contact

        if (isset($object->contractNumber)) {
            $model->contractNumber = $object->contractNumber;
        }

        if (isset($object->contractStartDate)) {
            $model->contractStartDate = Carbon::createFromFormat("d/m/Y", $object->contractStartDate)->timezone('America/Bogota');
        }

        if (isset($object->contractEndDate)) {
            $model->contractEndDate = Carbon::createFromFormat("d/m/Y", $object->contractEndDate)->timezone('America/Bogota');
        }

        $model->group_id = !isset($object->group->id) ? null : $object->group->id;

        $model->size = !isset($object->size->id) ? null : $object->size->value;

        $model->hasEconomicGroup = $object->hasEconomicGroup;

        if (isset($object->totalEmployee)) {
            $model->totalEmployee = $object->totalEmployee ? $object->totalEmployee->value : null;
        }

        if (isset($object->riskLevel)) {
            $model->riskLevel = $object->riskLevel ? $object->riskLevel->value : null;
        }

        if (isset($object->riskClass)) {
            $model->riskClass = $object->riskClass ? $object->riskClass->value : null;
        }


        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            $model->isDeleted = 0;

            // Guarda
            $model->save();
        }

        $customerModel = Customer::find($model->id);

        //DAB->20200717: SPRINT 15
        //REMOVE UPDATE USER ROLE BASE ON CUSTOMER SIZE
        //CustomerDto::updateUserRole($customerModel);

        return Customer::find($model->id);
    }

    public static function fillAndSaveModelUnit($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        // limpiar las unidades
        $model->unities()->delete();

        // Datos de contacto
        if ($object->unities) {
            foreach ($object->unities as $c) {
                if (isset($c->id) && isset($c->value) && isset($c->agents) && $c->value != "-S-") {
                    foreach ($c->agents as $agent) {
                        if (!isset($agent->selected)) {
                            continue;
                        }
                        $mdl = new CustomerAgent();
                        $mdl->customer_id = $model->id;
                        $mdl->agent_id = $agent->selected->id;
                        $mdl->type = $c->value;
                        $mdl->save();
                    }
                }
            }
        }


        return Customer::find($model->id);
    }

    public static function fillAndSaveModelContacts($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        if (isset($object->maincontacts) && $object->maincontacts) {
            foreach ($object->maincontacts as $c) {
                if (isset($c->id) && isset($c->value) && $c->role != null && $c->value != "" && isset($c->role->value)) {
                    if ($c->id) {
                        if (!($mdl = Contact::find($c->id))) {
                            $mdl = new Contact();
                        }
                    } else {
                        $mdl = new Contact();
                    }
                    $mdl->customer_id = $model->id;
                    $mdl->role = $c->role->value;
                    $mdl->name = $c->value;
                    $mdl->firstName = $c->firstname;
                    $mdl->lastName = $c->lastname;
                    $mdl->save();

                    foreach ($c->info as $info) {
                        if (isset($info->id) && isset($info->type->value) && isset($info->value) && $info->value != "-S-") {
                            if ($info->id) {
                                if (!($mdlId = InfoDetail::find($info->id))) {
                                    $mdlId = new InfoDetail();
                                }
                            } else {
                                $mdlId = new InfoDetail();
                            }
                            $mdlId->entityId = $mdl->id;
                            $mdlId->entityName = get_class($mdl);
                            $mdlId->type = ($info->type) ? $info->type->value : null;
                            $mdlId->value = $info->value;
                            $mdlId->save();
                        }
                    }

                    $model->maincontacts()->add($mdl);
                }
            }
        }

        return Customer::find($model->id);
    }

    public static function fillAndSaveModelInfoDetail($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        if ($object->contacts) {
            foreach ($object->contacts as $contact) {
                if (isset($contact->id) && $contact->type != null && isset($contact->type->value) && isset($contact->value) && $contact->value != "-S-") {
                    if ($contact->id) {
                        if (!($mdl = InfoDetail::find($contact->id))) {
                            $mdl = new InfoDetail();
                        }
                    } else {
                        $mdl = new InfoDetail();
                    }
                    $mdl->entityId = $model->id;
                    $mdl->entityName = get_class($model);
                    $mdl->type = ($contact->type) ? $contact->type->value : null;
                    $mdl->value = $contact->value;
                    $mdl->save();
                }
            }
        }

        return Customer::find($model->id);
    }

    public static function fillAndSaveModelParameters($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        if (isset($object->projectTypes)) {
            CustomerParameterDTO::bulkInsert($object->projectTypes, $model->id);
        }

        if (isset($object->userSkills)) {
            CustomerParameterDTO::bulkInsert($object->userSkills, $model->id);
        }

        if (isset($object->employeeDocumentsTypeList)) {
            CustomerParameterDTO::bulkInsert($object->employeeDocumentsTypeList, $model->id);
        }

        if (isset($object->assignedHours)) {
            CustomerParameterDTO::bulkInsert($object->assignedHours, $model->id);
        }

        if (isset($object->projectTaskTypes)) {
            CustomerParameterDTO::bulkInsert($object->projectTaskTypes, $model->id);
        }

        if (isset($object->extraContactInformation)) {
            CustomerParameterDTO::bulkInsert($object->extraContactInformation, $model->id);
        }

        if (isset($object->documentsTypeList)) {
            CustomerParameterDTO::bulkInsert($object->documentsTypeList, $model->id);
        }

        if (isset($object->contactTypeList)) {
            CustomerParameterDTO::bulkInsert($object->contactTypeList, $model->id);
        }

        if (isset($object->contractorTypeList)) {
            CustomerParameterDTO::bulkInsert($object->contractorTypeList, $model->id);
        }

        if (isset($object->userNotificationList)) {
            CustomerParameterDTO::bulkInsert($object->userNotificationList, $model->id);
        }

        if (isset($object->officeTypeMatrixSpecialList)) {
            CustomerParameterDTO::bulkInsert($object->officeTypeMatrixSpecialList, $model->id);
        }

        if (isset($object->businessUnitMatrixSpecialList)) {
            CustomerParameterDTO::bulkInsert($object->businessUnitMatrixSpecialList, $model->id);
        }

        if (isset($object->experienceVRList)) {
            CustomerParameterDTO::bulkInsert($object->experienceVRList, $model->id);
        }

        return Customer::find($model->id);
    }

    public static function fillAndSaveModelDocumentTypeParameters($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        if (isset($object->employeeDocumentsTypeList)) {
            CustomerParameterDTO::bulkInsert($object->employeeDocumentsTypeList, $model->id);
        }

        return Customer::find($model->id);
    }

    public static function fillAndSaveModelQuick($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // Razon Social
        $model->businessName = $object->businessName;

        // Estado
        $model->status = 1;

        //Tipo de Documento
        $model->documentType = $object->documentType->value == "-S-" ? null : $object->documentType->value;

        //Numero de Documento
        $model->documentNumber = $object->documentNumber;

        $model->type = 'pn';

        // Pais
        $model->country_id = !isset($object->country->id) ? null : $object->country->id;

        // Departamento
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;

        // Municipio
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;

        $model->isDeleted = 0;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/
        // Datos de contacto
        if ($object->contacts) {
            foreach ($object->contacts as $contact) {
                if (isset($contact->id) && $contact->type != null && isset($contact->type->value) && isset($contact->value) && $contact->value != "-S-") {
                    if ($contact->id) {
                        if (!($mdl = InfoDetail::find($contact->id))) {
                            $mdl = new InfoDetail();
                        }
                    } else {
                        $mdl = new InfoDetail();
                    }
                    $mdl->entityId = $model->id;
                    $mdl->entityName = get_class($model);
                    $mdl->type = ($contact->type) ? $contact->type->value : null;
                    $mdl->value = $contact->value;
                    $mdl->save();
                }
            }
        }


        return Customer::find($model->id);
    }

    public static function fillAndInsert($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Customer::find($object->id))) {
                // No existe
                $model = new Customer();
                $isEdit = false;
            }
        } else {
            $model = new Customer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // Razon Social
        $model->businessName = $object->businessName;

        // Actividad Economic
        $model->economicActivity = $object->economicActivity && isset($object->economicActivity->id) ? $object->economicActivity->id : null;

        // Estado
        $model->status = $object->status->value == "-S-" ? null : $object->status->value;

        // Tipo de Cliente
        $model->type = "c";

        //Tipo de Documento
        $model->documentType = $object->documentType->value == "-S-" ? null : $object->documentType->value;

        $model->classification = $object->classification == null ? null : $object->classification->value;

        //Numero de Documento
        $model->documentNumber = $object->documentNumber;

        // Sitio Web
        $model->webSite = $object->webSite;

        // Arl
        $model->arl = $object->arl == null ? null : $object->arl->value;

        // Pais
        $model->country_id = !isset($object->country->id) ? null : $object->country->id;

        // Departamento
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;

        // Municipio
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;

        // Empleados Directos
        $model->directEmployees = $object->directEmployees;

        // Empleados Temporales
        $model->temporaryEmployees = $object->temporaryEmployees;

        // Empleados Temporales
        $model->temporalCompanyName = $object->temporalCompany;

        $model->group_id = !isset($object->group->id) ? null : $object->group->id;

        $model->size = !isset($object->size->id) ? null : $object->size->value;

        $model->hasEconomicGroup = $object->hasEconomicGroup;

        $model->isDeleted = 0;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        $customerModel = Customer::find($model->id);

        //DAB->20200717: SPRINT 15
        //REMOVE UPDATE USER ROLE BASE ON CUSTOMER SIZE
        //CustomerDto::updateUserRole($customerModel);

        return Customer::find($customerModel);
    }

    private static function updateUserRole($customer)
    {
        if ($customer != null) {

            if ($customer->size != null) {

                $roleName = '';

                switch ($customer->size) {
                    case "MC":
                        $roleName = "MICRO";
                        break;
                    case "PQ":
                        $roleName = "PEQUEÑA";
                        break;
                    case "MD":
                        $roleName = "MEDIANA";
                        break;
                    case "GD":
                        $roleName = "GRANDE";
                        break;
                }

                if ($roleName != '') {

                    $users = DB::table('users')->where('company', $customer->id)->get();

                    foreach ($users as $user) {

                        if ($user->wg_type == 'customerAdmin' || $user->wg_type == 'customerUser') {

                            $customerUser = DB::table('wg_customer_user')->where('user_id', $user->id)->first();

                            $isUserApp = $customerUser ? $customerUser->isUserApp == 1 : false;

                            if (!$isUserApp) {
                                DB::table('shahiemseymor_assigned_roles')->where('user_id', '=', $user->id)->delete();

                                $role = DB::table('shahiemseymor_roles')->where('name', $roleName)->first();

                                if ($role != null) {
                                    DB::table('shahiemseymor_assigned_roles')->insert(
                                        ['user_id' => $user->id, 'role_id' => $role->id]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /// ::: METODOS PRIVADOS DE CADA DTO

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {
        if ($fmt_response != "1") {
            // parse model
            switch ($fmt_response) {
                case "2":
                    $this->getReportSummaryPrg($model);
                    break;
                case "3":
                    $this->getReportYear($model);
                    break;
                case "4":
                    $this->getInfoBasicShort($model);
                    break;
                case "5":
                    $this->getInfoBasicContractor($model);
                    break;
                case "6":
                    $this->getInfoBasicUnit($model);
                    break;
                case "7":
                    $this->getInfoBasicEmployeeDocumentType($model);
                default:
                    $this->getInfoBasic($model);
            }
        } else {
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
        }
        return $this;
    }

    public static function parse($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof Customer) {
                    $parsed[] = (new CustomerDto())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDto())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Customer) {
            return (new CustomerDto())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDto();
            }
        }
    }

    public static function parseUnit($info)
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof Customer) {
                    $parsed[] = (new CustomerDto())->parseModel($model, "6");
                } else {
                    $parsed[] = (new CustomerDto())->parseArray($model, "6");
                }
            }
            return $parsed;
        } else if ($info instanceof Customer) {
            return (new CustomerDto())->parseModel($data, "6");
        }
    }

    private function parseArray($model, $fmt_response = "1")
    {
        switch ($fmt_response) {
            case "2":
                $this->getReportSummaryPrg($model);
                break;
            case "3":
                $this->getReportYear($model);
                break;
            case "4":
                $this->getInfoBasicShort($model);
                break;
        }

        return $this;
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
