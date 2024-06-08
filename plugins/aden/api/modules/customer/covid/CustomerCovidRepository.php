<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Covid;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CovidQuestion\CustomerCovidQuestionRepository;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeRepository;
use AdeN\Api\Modules\Customer\Contractor\CustomerContractorRepository;
use DB;
use Exception;
use Log;
use Excel;
use Carbon\Carbon;
use Wgroup\Employee\EmployeeDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\Covid\Daily\CustomerCovidDailyModel;
use Illuminate\Pagination\Paginator;

class CustomerCovidRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerCovidModel());

        $this->service = new CustomerCovidService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo Personal", "name" => "personType"],
            ["alias" => "Tipo Identificación", "name" => "documentType"],
            ["alias" => "Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "fullName"],
            ["alias" => "Fecha Último Registro", "name" => "lastDate"],
            ["alias" => "Estado", "name" => "lastRiskLevelText"],
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_covid_head.id",
            "personType" => DB::raw("IF(wg_customer_covid_head.is_external=1,'EXTERNO','EMPLEADO') AS personType"),
            "documentType" => DB::raw("IF(wg_customer_covid_head.is_external=1,wg_customer_covid_head.document_type,wg_employee.documentType) AS documentType"),
            "documentNumber" => DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.document_number ELSE wg_employee.documentNumber END AS documentNumber"),
            "fullName" => DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.full_name ELSE wg_employee.fullName END AS fullName"),
            "workplace" => "wg_customer_config_workplace.name as workplace",
            "contractor" => "contractor.businessName as contractor",
            "lastDate" => "wg_customer_covid_head.latest_registration_date AS lastDate",
            "lastRiskLevelText" => "risk_level.name AS lastRiskLevelText",
            "lastRiskLevelColor" => "risk_level.code AS lastRiskLevelColor",
            "customerEmployeeId" => "wg_customer_covid_head.customer_employee_id",
            "customerId" => "wg_customer_covid_head.customer_id",
            "createdBy" => "wg_customer_covid_head.created_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_covid_head.customer_id');
        })->leftjoin("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_head.customer_employee_id');
        })->leftjoin("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('employee_document_type.value', '=', 'wg_employee.documentType');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('external_document_type')), function ($join) {
            $join->on('external_document_type.value', '=', 'wg_customer_covid_head.document_type');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid_head.customer_workplace_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_external_type')), function ($join) {
            $join->on('customer_external_type.value', '=', 'wg_customer_covid_head.external_type');
        })->leftjoin(DB::raw("wg_config_general AS risk_level"), function ($join) {
            $join->on('risk_level.value', '=', 'wg_customer_covid_head.latest_risk_level');
            $join->where('risk_level.type', '=', 'RISK_LEVEL_COVID_19');
        })->leftjoin(DB::raw("wg_customers AS contractor"), function ($join) {
            $join->on('contractor.id', '=', 'wg_customer_covid_head.contractor_id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allIndicator($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_covid.id",
            "registrationDate" => "wg_customer_covid.registration_date",
            "fullName" => DB::raw("IF(wg_customer_covid_head.is_external=1,wg_customer_covid_head.full_name,wg_employee.fullName) as fullName"),
            "questions" => "question.questions",
            "riskLevelText" => "risk_level.name AS riskLevelText",
            "riskLevelColor" => "risk_level.code AS riskLevelColor",
            "customerEmployeeId" => "wg_customer_covid_head.customer_employee_id",
            "riskLevel" => "wg_customer_covid.risk_level",
            "customerId" => "wg_customer_covid_head.customer_id",
            "day" => "wg_customer_covid.registration_date AS day",
            "period" => DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') AS period"),
            "workplaceId" => "wg_customer_covid_head.customer_workplace_id",
            "contractorId" => "wg_customer_covid_head.contractor_id",
            "isExternal" => "wg_customer_covid_head.is_external",
            "hasPersons" => DB::raw("IF(person.hasPersons >= 1 , 1, 0) AS hasPersons")
        ]);

        $this->parseCriteria($criteria);

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        $qQuestion = DB::table('wg_customer_covid_question')
            ->join("wg_customer_covid", function ($join) {
                $join->on('wg_customer_covid.id', '=', 'wg_customer_covid_question.customer_covid_id');
            })
            ->join("wg_customer_covid_head", function ($join) {
                $join->on('wg_customer_covid_head.id', '=', 'wg_customer_covid.customer_covid_head_id');
            })
            ->join(DB::raw("wg_covid_question"), function ($join) {
                $join->on('wg_covid_question.code', '=', 'wg_customer_covid_question.covid_question_code');
            })
            ->where('wg_customer_covid_question.is_active', 1)
            ->select(
                'wg_customer_covid_question.customer_covid_id',
                DB::raw("GROUP_CONCAT(wg_covid_question.name SEPARATOR ',') AS questions")
            )
            ->groupBy('wg_customer_covid_question.customer_covid_id');

        $qPeople = DB::table('wg_customer_covid_person_near')
            ->join("wg_customer_covid", function ($join) {
                $join->on('wg_customer_covid.id', '=', 'wg_customer_covid_person_near.customer_covid_id');
            })
            ->join("wg_customer_covid_head", function ($join) {
                $join->on('wg_customer_covid_head.id', '=', 'wg_customer_covid.customer_covid_head_id');
            })
            ->select(
                'wg_customer_covid_person_near.customer_covid_id',
                DB::raw("COUNT(*) AS hasPersons")
            )
            ->groupBy('wg_customer_covid_person_near.customer_covid_id');

        if ($customerId) {
            $qQuestion->where('wg_customer_covid_head.customer_id', $customerId->value);
            $qPeople->where('wg_customer_covid_head.customer_id', $customerId->value);
        }

        $query = $this->query();

        $query
            ->join("wg_customer_covid", function ($join) {
                $join->on("wg_customer_covid_head.id", "=", "wg_customer_covid.customer_covid_head_id");
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_covid_head.customer_id');
            })
            ->leftJoin("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_head.customer_employee_id');
            })
            ->leftJoin("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftJoin(DB::raw("wg_config_general AS risk_level"), function ($join) {
                $join->on('risk_level.value', '=', 'wg_customer_covid.risk_level');
                $join->where('risk_level.type', '=', 'RISK_LEVEL_COVID_19');
            })
            ->leftjoin(DB::raw("({$qPeople->toSql()}) AS person"), function ($join) {
                $join->on('person.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->mergeBindings($qPeople)
            ->leftjoin(DB::raw("({$qQuestion->toSql()}) AS question"), function ($join) {
                $join->on('question.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->mergeBindings($qQuestion);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {

        $documentType = $entity->documentType->value;
        $documentNumber = $entity->documentNumber;
        $customerEmployeeId = !$entity->isExternal ? $entity->employee->id : 0;


        if ($entity->isExternal) {
            $entityToCompare = $this->model
                ->where('customer_id', $entity->customerId)
                ->where('document_type', $documentType)
                ->where('document_number', $documentNumber)
                ->first();
        } else {
            $entityToCompare = $this->model
                ->where('customer_id', $entity->customerId)
                ->where('customer_employee_id', $customerEmployeeId)
                ->first();
        }

        if ((!is_null($entityToCompare) && $entity->id == 0)) {
            return false;
        }

        return true;
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
        $entityModel->customerEmployeeId = !$entity->isExternal && $entity->employee ? $entity->employee->id : null;
        $entityModel->documentType = $entity->documentType ? $entity->documentType->value : null;
        $entityModel->documentNumber = $entity->isExternal ? $entity->documentNumber : null;
        $entityModel->firstName = $entity->isExternal ? $entity->firstName : null;
        $entityModel->lastName = $entity->isExternal ? $entity->lastName : null;
        $entityModel->fullName = $entity->isExternal ? "{$entity->firstName} {$entity->lastName}" : null;
        $entityModel->contractType = !$entity->isExternal && $entity->employee->contractType ? $entity->employee->contractType->value : null;
        $entityModel->customerWorkplaceId = $entity->customerWorkplaceId ? $entity->customerWorkplaceId->id : null;
        $entityModel->job = !$entity->isExternal && $entity->employee->job ? $entity->employee->job->id : null;
        $entityModel->externalType = $entity->isExternal && $entity->externalType ? $entity->externalType->value : null;
        $entityModel->observation = $entity->observation;
        $entityModel->birthdate = !$entity->isExternal ? NULL : Carbon::parse($entity->birthDate)->timezone('America/Bogota');
        $entityModel->age = !$entity->isExternal ? NULL : $entity->age;
        $entityModel->telephone = $entity->telephone;
        $entityModel->mobile = $entity->mobile;
        $entityModel->address =  $entity->address;
        $entityModel->origin =  $entity->origin;
        $entityModel->isExternal = $entity->isExternal;
        $entityModel->contractorId = $entity->contractorId ? $entity->contractorId->id : 0;
        $entityModel->registrationDate = Carbon::parse($entity->registrationDate)->timezone('America/Bogota');


        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if (!empty($entityModel->customerEmployeeId)) {
            $dirUp = false;
            $celUp = false;
            $telUp = false;
            foreach ($entity->employee->entity->details as $detailInfo) {
                // existentes
                switch ($detailInfo->type->value) {
                    case 'dir':
                        $dirUp = true;
                        break;
                    case 'cel':
                        $celUp = true;
                        break;
                    case 'tel':
                        $telUp = true;
                        break;
                }
            }

            // almacenamos solo los nuevos
            $emptyDir = (object)["id" => 0, "type" => [], "value" => null];
            $emptyCel = (object)["id" => 0, "type" => [], "value" => null];
            $emptyTel = (object)["id" => 0, "type" => [], "value" => null];
            $entity->employee->entity->details = [];

            foreach ($entity->informationList as $list) {
                if ($list->value != "dir" && $list->value != "cel" && $list->value != "tel") {
                    continue;
                }
                switch ($list->value) {
                    case 'dir':
                        if (!empty($entityModel->address) && !$dirUp) {
                            $emptyDir->value = $entityModel->address;
                            $emptyDir->type = $list;
                            $entity->employee->entity->details[] = $emptyDir;
                        }
                        break;
                    case 'cel':
                        if (!empty($entityModel->mobile) && !$celUp) {
                            $emptyCel->value = $entityModel->mobile;
                            $emptyCel->type = $list;
                            $entity->employee->entity->details[] = $emptyCel;
                        }
                        break;
                    case 'tel':
                        if (!empty($entityModel->telephone) && !$telUp) {
                            $emptyTel->value = $entityModel->telephone;
                            $emptyTel->type = $list;
                            $entity->employee->entity->details[] = $emptyTel;
                        }
                        break;
                }
            }

            EmployeeDTO::employeeUpdateInfoDetail($entity->employee->entity);
            if ($entity->updateEmployeeBirthdate) {
                $entity->employee->entity->birthDate = Carbon::parse($entity->birthDate);
                $entity->employee->entity->age = $entity->age;
                EmployeeDTO::employeeUpdateBirthDateAndAge($entity->employee->entity);
            }
        }

        $entity->id = $entityModel->id;
        $entity->birthDate = Carbon::parse($entity->birthDate)->timezone('America/Bogota');
        $entity->registrationDate = $entityModel->registrationDate;
        return $entity;
    }

    public static function refreshLastInfo(CustomerCovidDailyModel $daily)
    {
        $reposity = new self;
        $covidHead = $reposity->find($daily->customerCovidHeadId);
        $covidHead->latestRegistrationDate = $daily->registrationDate;
        $covidHead->latestRiskLevel = $daily->riskLevel;
        $covidHead->save();
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/PlantillaCovid19.xlsx";

        $workplaceData = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name
            ];
        }, $this->service->getWorkplaceList($customerId));

        $contractor = new CustomerContractorRepository();
        $criteria = (object)["parentId" => $customerId];

        $contractorData = array_map(function ($row) {
            return [
                'NOMBRE' => $row->item
            ];
        }, $contractor->getCustomerContractorList($criteria));


        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($workplaceData, $contractorData) {
            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($workplaceData, null, 'G1', false);
            $sheet->fromArray($contractorData, null, 'E1', false);
            $sheet = $file->setActiveSheetIndex(0);
        })->download('xlsx');
    }

    public function parseModelWithRelations(CustomerCovidModel $model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();
            $employeeRepository = new CustomerEmployeeDTO();
            $employee = !$model->isExternal == 1 ? $employeeRepository->find($model->customerEmployeeId, 2) : null;

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;

            if (!$model->isExternal) {
                $entity->employee = [
                    "id" => $employee ? $employee->id : null,
                    "customerId" => $model->customerId,
                    "contractType" => $employee ? $employee->contractType :  $model->getContractType(),
                    "job" => $employee ? $employee->job :  $model->job,
                    "occupation" => $employee ? $employee->occupation :  null,
                    "type" => $employee ? $employee->type :  null,
                    "workPlace" => $model->getWorkplace(),
                    "documentType" => $employee ? $employee->entity->documentType :  $model->getDocumentType(),
                    "documentNumber" => $employee ? $employee->entity->documentNumber :  $model->documentNumber,
                    "birthDate" => Carbon::parse($employee->entity->birthDate),
                    "age" => $employee->entity->age,
                    "firstName" => $employee ? $employee->entity->firstName :  $model->firstName,
                    "lastName" => $employee ? $employee->entity->lastName :  $model->lastName,
                    "fullName" => $employee ? $employee->entity->fullName :  $model->fullName,
                    "entity" => $employee ? $employee->entity : null
                ];
            } else {
                $entity->employee = null;
            }

            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->customerWorkplaceId = $model->getWorkplace();
            $entity->firstName = $model->firstName;
            $entity->lastName = $model->lastName;
            $entity->fullName = $model->fullName;
            $entity->externalType = $model->getExternalType();
            $entity->contractorId = $model->getContractor();
            $entity->contractor = $model->contractor;
            $entity->birthDate = $model->birthdate ? Carbon::parse($model->birthdate) : null;
            $entity->age = $model->age;
            $entity->observation = $model->observation;
            $entity->telephone = $employee ? $this->getEmployeeInfo($employee->entity->details, 'tel') : $model->telephone;
            $entity->mobile = $employee ? $this->getEmployeeInfo($employee->entity->details, 'cel') : $model->mobile;
            $entity->address = $employee ? $this->getEmployeeInfo($employee->entity->details, 'dir') : $model->address;
            $entity->isExternal = $model->isExternal == 1;
            $entity->origin = $model->origin;
            $entity->registrationDate = $model->registrationDate ? Carbon::parse($model->registrationDate) : null;


            return $entity;
        } else {
            return null;
        }
    }

    private function getEmployeeInfo($data, $type)
    {
        $value = null;
        foreach ($data as $info) {
            if ($info->type && isset($info->type->attributes) && $info->type->attributes['value'] == $type) {
                $value = $info->value;
            }
        }
        return $value;
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Historico_Covid_Personal' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Registros', $data);
    }

    public function exportExcelEmployee($criteria)
    {
        $data = $this->service->getExportData($criteria, "employee");
        $filename = 'Historico_Covid_Employee_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Detalle', $data);
    }

    public function exportExcelExternal($criteria)
    {
        $data = $this->service->getExportData($criteria, "external");
        $filename = 'Historico_Covid_External_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Detalle', $data);
    }

    public function getPeriodList($criteria)
    {
        return $this->service->getPeriodList($criteria);
    }

    public function getDateList($criteria)
    {
        return $this->service->getDateList($criteria);
    }

    public function getGenreCharPie($criteria)
    {
        return $this->service->getGenreCharPie($criteria);
    }

    public function getPregnantCharPie($criteria)
    {
        return $this->service->getPregnantCharPie($criteria);
    }

    public function getFeverCharBar($criteria)
    {
        return $this->service->getFeverCharBar($criteria);
    }

    public function getEmployeeCharBar($criteria)
    {
        return $this->service->getEmployeeCharBar($criteria);
    }

    public function getEmployeeWorkplaceCharBar($criteria)
    {
        return $this->service->getEmployeeWorkplaceCharBar($criteria);
    }

    public function getRiskLevelCharBar($criteria)
    {
        return $this->service->getRiskLevelCharBar($criteria);
    }

    public function getOximetriaCharBar($criteria)
    {
        return $this->service->getOximetriaCharBar($criteria);
    }

    public function getPulsometriaCharBar($criteria)
    {
        return $this->service->getPulsometriaCharBar($criteria);
    }

    public function getCovidWorkplaceList($criteria)
    {
        return $this->service->getCovidWorkplaceList($criteria);
    }

    public function getCovidContractorList($criteria)
    {
        return $this->service->getCovidContractorList($criteria);
    }
}
