<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Covid\Daily;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CovidQuestion\CustomerCovidQuestionRepository;
use AdeN\Api\Modules\Customer\Covid\DailyTemperature\CustomerCovidDailyTemperatureModel;
use DB;
use Exception;
use Log;
use Excel;
use Carbon\Carbon;
use AdeN\Api\Modules\Customer\Covid\CustomerCovidRepository;

class CustomerCovidDailyRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerCovidDailyModel());

        $this->service = new CustomerCovidDailyService();
    }

    public function all($criteria)
    {
        $customerCovidHeadId = CriteriaHelper::getMandatoryFilter($criteria, 'customerCovidHeadId');
        $qSympoms = $this->service->getRelationQuestion($customerCovidHeadId);
        $this->setColumns([
            "id" => "wg_customer_covid.id",
            "registrationDate" => "wg_customer_covid.registration_date",
            "symptoms" => "symptoms.symptoms",
            "riskLevel" => DB::raw("riskLevel.name AS riskLevel"),
            "origin" => "wg_customer_covid.origin",
            "riskLevelColor" => DB::raw("riskLevel.code AS riskLevelColor"),
            "customerCovidHeadId" => "wg_customer_covid.customer_covid_head_id",
            "createdBy" => "wg_customer_covid.created_by"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->join("wg_customer_covid_head", function ($join) {
            $join->on('wg_customer_covid.customer_covid_head_id', '=', 'wg_customer_covid_head.id');
        })
        ->leftJoin(DB::raw("wg_config_general AS riskLevel"), function ($join) {
            $join->on('wg_customer_covid.risk_level', '=', 'riskLevel.value');
            $join->where('riskLevel.type', '=', 'RISK_LEVEL_COVID_19');
        });
        $query->leftJoin(DB::raw("({$qSympoms->toSql()}) AS symptoms"), function ($join) {
            $join->on("wg_customer_covid.id", "=", "symptoms.customer_covid_id");
        })
        ->mergeBindings($qSympoms);

        if (!empty($criteria->selectedMonth)) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = {$criteria->selectedMonth}");
        }
        
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function canInsert($entity)
    {
        $entityToCompare = $this->model
        ->where('customer_covid_head_id', $entity->customerCovidHeadId)
        ->whereRaw('registration_date = ?', [Carbon::parse($entity->registrationDate)->toDateString()])
        ->first();
        
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

        $entityModel->id = $entity->id;
        $entityModel->customerCovidHeadId = $entity->customerCovidHeadId;
        $entityModel->riskLevel = $entity->riskLevel->riskLevelValue;
        $entityModel->origin = $entity->origin;
        $entityModel->registrationDate = Carbon::parse($entity->registrationDate)->timezone('America/Bogota');

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $maxRegister = $this->service->getLastDate($entityModel->customerCovidHeadId);
        if (is_null($maxRegister) || $maxRegister->id == $entityModel->id) {
            CustomerCovidRepository::refreshLastInfo($entityModel);
        }

        if (isset($entity->questionList)) {
            CustomerCovidQuestionRepository::bulkInsertOrUpdate($entity->questionList, $entityModel->id);
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }

    public function parseModelWithRelations(CustomerCovidDailyModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->customerCovidHeadId = $model->customerCovidHeadId;
            $entity->origin = $model->origin;
            $entity->riskLevel = $model->getRiskLevel();
            $entity->registrationDate = $model->registrationDate ? Carbon::parse($model->registrationDate) : null;
            $entity->questionList = $model->getQuestionList();

            $parameter = CustomerCovidDailyTemperatureModel::whereCustomerCovidId($model->id)
                        ->select(
                            DB::raw("MAX(temperature) AS temperature"),
                            DB::raw("MIN(oximetria) AS oximetria"),
                            DB::raw("MAX(pulse) AS pulse1"),
                            DB::raw("MIN(pulse) AS pulse2")
                        )
                        ->first();

            if($parameter && !is_null($parameter->temperature)) {
                if((float)$parameter->temperature >= 37.3 ||
                (float)$parameter->oximetria > 0 && (float)$parameter->oximetria < 95 ||
                (float)$parameter->pulse1 > 0 && (float)$parameter->pulse1 > 100 ||
                (float)$parameter->pulse2 > 0 && (float)$parameter->pulse2 < 60) {
                    $entity->conditionalRiskLevel = "A";
                }

                foreach($entity->questionList as $question) {
                    if($question->covidQuestionCode == "004"){
                        if((float)$parameter->temperature >= 37.3){
                            $question->isActive = true;
                        } else {
                            $question->isActive = false;
                        }
                        break;
                    }
                }
            }

            return $entity;
        } else {
            return null;
        }
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Historico_Covid_Persona_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Registros', $data);
    }

    public function setFever($parentId)
    {
        $this->service->setFever($parentId);
    }

    public function quitFever($parentId)
    {
        $this->service->quitFever($parentId);
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

    public function getCovidWorkplaceList($criteria)
    {
        return $this->service->getCovidWorkplaceList($criteria);
    }

    public function getCovidContractorList($criteria)
    {
        return $this->service->getCovidContractorList($criteria);
    }

    public function mergeInfo()
    {
        return $this->service->mergeInfo();
    }
}
