<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Employee;

use AdeN\Api\Modules\EmployeeInformationDetail\EmployeeInformationDetailRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\EmployeeDemographic\EmployeeDemographic;
use Wgroup\EmployeeDemographic\EmployeeDemographicDTO;
use Wgroup\EmployeeInfoDetail\EmployeeInfoDetail;
use Wgroup\EmployeeInfoDetail\EmployeeInfoDetailDTO;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\InfoDetailDto;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class EmployeeDTO
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
        $this->id = $model->id;

        $this->documentType = $model->getDocumentType();

        $this->documentNumber = $model->documentNumber;

        $this->expeditionPlace = $model->expeditionPlace;

        $this->expeditionDate = $model->expeditionDate ? Carbon::parse($model->expeditionDate) : null;

        $this->firstName = $model->firstName;

        $this->lastName = $model->lastName;

        $this->fullName = $model->fullName;

        $this->birthDate = $model->birthdate ? Carbon::parse($model->birthdate) : null;

        $this->gender = $model->getGender();

        $this->profession = $model->getProfession();

        $this->eps = $model->getEps();

        $this->afp = $model->getAfp();

        $this->arl = $model->getArl();

        $this->country = $model->country;

        $this->state = $model->state;

        $this->town = $model->town;


        $this->rh = $model->getRh();

        $this->riskLevel = $model->riskLevel;


        $this->neighborhood = $model->neighborhood;

        $this->observation = $model->observation;

        $this->isActive = $model->isActive;

        $now = Carbon::now();

        $this->age = $model->birthdate ? Carbon::parse($model->birthdate)->diffInYears($now) : 0;

        $this->logo = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);

        $this->details = EmployeeInfoDetailDTO::parse($model->getInfoDetail());

        $this->illnesses = $model->getIllness();

        $this->averageIncome = $model->averageIncome;
        $this->hasPeopleInCharge = $model->hasPeopleInCharge == 1;
        $this->qtyPeopleInCharge = $model->qtyPeopleInCharge;
        $this->hasChildren = $model->hasChildren == 1;
        $this->typeHousing = $model->getTypeHousing();
        $this->antiquityCompany = $model->getAntiquityCompany();
        $this->antiquityJob = $model->getAntiquityJob();
        $this->frequencyPracticeSports = $model->getFrequencyPracticeSports();
        $this->isPracticeSports = $model->isPracticeSports == 1;
        $this->frequencyDrinkAlcoholic = $model->getFrequencyDrinkAlcoholic();
        $this->isDrinkAlcoholic = $model->isDrinkAlcoholic == 1;
        $this->frequencySmokes = $model->getFrequencySmokes();
        $this->isSmokes = $model->isSmokes == 1;
        $this->isDiagnosedDisease = $model->isDiagnosedDisease == 1;

        $this->stratum = $model->getStratum();
        $this->civilStatus = $model->getCivilStatus();
        $this->scholarship = $model->getScholarship();
        $this->race = $model->getRace();
        $this->workingHoursPerDay = $model->workingHoursPerDay;
        $this->workArea = $model->getWorkArea();

        $this->age =  $model->birthdate ? Carbon::parse($model->birthdate)->diffInYears(Carbon::today()) : 0;

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }


        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Employee::find($object->id))) {
                // No existe
                $model = new Employee();
                $isEdit = false;
                // $model->documentType = $object->documentType != null ? $object->documentType->value : null;
                // $model->documentNumber = $object->documentNumber;

                // $model = Employee::where("documentType", $model->documentType)
                //     ->where("documentNumber", $object->documentNumber)
                //     ->first();

                // if (!$model) {
                //     $isEdit = false;
                //     $model = new Employee();
                // }
            }
        } else {
            $model = new Employee();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->documentType = $object->documentType != null ? $object->documentType->value : null;
        $model->documentNumber = $object->documentNumber;
        $model->expeditionPlace = $object->expeditionPlace;
        $model->expeditionDate = $object->expeditionDate ? Carbon::parse($object->expeditionDate) : null;
        $model->firstName = $object->firstName;
        $model->lastName = $object->lastName;
        $model->fullName = $object->firstName . ' ' . $object->lastName;
        $model->birthdate = $object->birthDate ? Carbon::parse($object->birthDate) : null;
        $model->gender = $object->gender->value;
        $model->profession = $object->profession ? $object->profession->value : null;
        $model->eps = $object->eps ? $object->eps->value : null;
        $model->afp = $object->afp ? $object->afp->value : null;
        $model->arl = $object->arl ? $object->arl->value : null;
        $model->country_id = !isset($object->country->id) ? null : $object->country->id;
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;

        $model->rh = !isset($object->rh->value) ? null : $object->rh->value;
        $model->riskLevel = !isset($object->riskLevel) ? null : $object->riskLevel;

        $model->neighborhood = $object->neighborhood;
        $model->observation = $object->observation;
        //$model->isActive = $object->isActive;

        $model->age = $object->birthDate ? Carbon::parse($object->birthDate)->diffInYears(Carbon::today()) : 0;

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

        // limpiar la informacion de contacto
        foreach ($model->getInfoDetail() as $infoDetail) {
            $infoDetail->delete();
        }

        if ($object->details) {
            foreach ($object->details as $contactInfo) {
                if (isset($contactInfo->id) && $contactInfo->type != null && $contactInfo->value != '') {
                    $infoDetail = new EmployeeInfoDetail();
                    $infoDetail->entityId = $model->id;
                    $infoDetail->entityName = get_class($model);
                    $infoDetail->type = ($contactInfo->type) ? $contactInfo->type->value : null;
                    $infoDetail->value = $contactInfo->value;
                    $infoDetail->save();
                }
            }
        }

        return Employee::find($model->id);
    }

    public static function  fillAndSaveDemographicModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Employee::find($object->id))) {
                // No existe
                $model = new Employee();
                $isEdit = false;
            }
        } else {
            $model = new Employee();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->averageIncome = $object->averageIncome;
        $model->typeHousing = ($object->typeHousing != null) ? $object->typeHousing->value : null;
        $model->antiquityCompany = ($object->antiquityCompany != null) ? $object->antiquityCompany->value : null;
        $model->antiquityJob = ($object->antiquityJob != null) ? $object->antiquityJob->value : null;
        $model->frequencyPracticeSports = ($object->frequencyPracticeSports != null) ? $object->frequencyPracticeSports->value : null;
        $model->frequencyDrinkAlcoholic = ($object->frequencyDrinkAlcoholic != null) ? $object->frequencyDrinkAlcoholic->value : null;
        $model->frequencySmokes = ($object->frequencySmokes != null) ? $object->frequencySmokes->value : null;

        $model->hasPeopleInCharge = $object->hasPeopleInCharge;
        $model->qtyPeopleInCharge = $object->qtyPeopleInCharge;
        $model->hasChildren = $object->hasChildren;
        $model->isPracticeSports = $object->isPracticeSports;
        $model->isDrinkAlcoholic = $object->isDrinkAlcoholic;
        $model->isSmokes = $object->isSmokes;
        $model->isDiagnosedDisease = $object->isDiagnosedDisease;

        $model->stratum = ($object->stratum != null) ? $object->stratum->value : null;
        $model->civilStatus = ($object->civilStatus != null) ? $object->civilStatus->value : null;
        $model->scholarship = ($object->scholarship != null) ? $object->scholarship->value : null;
        $model->race = ($object->race != null) ? $object->race->value : null;
        $model->workingHoursPerDay = $object->workingHoursPerDay;
        $model->workArea = ($object->race != null) ? $object->workArea->value : null;

        $model->country_id = !isset($object->country->id) ? null : $object->country->id;
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;

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

        $demographicRepository = new EmployeeDemographicDTO();

        EmployeeDemographic::whereCategory('illness')->whereEmployeeId($model->id)->delete();

        $demographicRepository->bulkInsert($object->illnesses, $model->id);

        return Employee::find($model->id);
    }

    public static function fillAndQuickSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = Employee::find($object->id))) {
                // No existe
                $model = new Employee();
                $isEdit = false;
            }
        } else {
            $model = new Employee();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->documentType = $object->documentType != null ? $object->documentType->value : null;
        $model->documentNumber = $object->documentNumber;
        $model->firstName = $object->firstName;
        $model->lastName = $object->lastName;
        $model->fullName = $object->firstName . ' ' . $object->lastName;
        $model->gender = isset($object->gender) && $object->gender ? $object->gender->value : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn ? $userAdmn->id : -1;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn ? $userAdmn->id : -1;
            $model->updatedBy = $userAdmn ? $userAdmn->id : -1;

            // Guarda
            $model->save();
        }

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/


        return Employee::find($model->id);
    }

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
                case "1":
                    $this->getInfoBasic($model);
                    break;
                default:
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
                if ($model instanceof Employee) {
                    $parsed[] = (new EmployeeDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new EmployeeDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Employee) {
            return (new EmployeeDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new EmployeeDTO();
            }
        }
    }

    private function parseArray($model, $fmt_response = "1")
    {
        switch ($fmt_response) {
            case "1":
                //$this->getBasicInfoSummary($model);
                break;
            default:
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

    public static function employeeUpdateInfoDetail($entity)
    {

        try {

            if (!$entity) {
                return false;
            }

            $model = new Employee();
            if ($entity->details) {
                foreach ($entity->details as $contactInfo) {
                    if (isset($contactInfo->id) && $contactInfo->id == 0) {
                        $infoDetail = new EmployeeInfoDetail();
                        $infoDetail->entityId = $entity->id;
                        $infoDetail->entityName = get_class($model);
                        $infoDetail->type = ($contactInfo->type) ? $contactInfo->type->value : null;
                        $infoDetail->value = $contactInfo->value;
                        $infoDetail->save();
                    }
                }
            }
        } catch (\Throwable $th) {
            throw new Exception("Error Processing Info Detail Employee", 1);
        }
    }

    public static function employeeUpdateBirthDateAndAge($object)
    {

        $userAdmn = Auth::getUser();
        if (!$object) {
            return false;
        }

        $model = Employee::find($object->id);
        $model->birthdate = $object->birthDate;
        $model->age = $object->age;

        $model->updatedBy = $userAdmn->id;
        $model->save();
        $model->touch();
    }
}
