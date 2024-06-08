<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\VrEmployee\ExperienceObservation\ExperienceObservationRepository;
use AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer\SceneAnswerRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class ExperienceAnswerRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ExperienceAnswerModel());
        $this->service = new ExperienceAnswerService();
    }

    public function observations($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_employee_answer_experience.id",
            "experience" => "experience_vr.item AS experience",
            "observationType" => "experience_scene_observation_type.item AS observationType",
            "observation" => "wg_customer_vr_employee_experience_observation.observation_value AS observation",
            "customerVrEmployeeId" => "wg_customer_vr_employee_answer_experience.customer_vr_employee_id AS customerVrEmployeeId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->join("wg_customer_vr_employee_experience_observation",function($join){
            $join->on("wg_customer_vr_employee_answer_experience.id","=","wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id");
        })->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.experience_code', '=','experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_observation_type')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.observation_type', '=','experience_scene_observation_type.value');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function countObservations($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_employee_answer_experience.id",
            "experience" => "obs.experience",
            "observationType" => "obs.obsType AS observationType",
            "total" => "obs.total",
            "selectedYear" => DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS selectedYear"),
            "customerId" => "wg_customer_vr_employee.customer_id"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $selectedYear = CriteriaHelper::getMandatoryFilter($criteria, 'selectedYear');
        
        $queryObs = DB::table("wg_customer_vr_employee_experience_observation")
                    ->join("wg_customer_vr_employee_answer_experience", function ($join) {
                        $join->on('wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id', '=','wg_customer_vr_employee_answer_experience.id');
                    })
                    ->join("wg_customer_vr_employee", function ($join) {
                        $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=','wg_customer_vr_employee.id');
                    })
                    ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                        $join->on('wg_customer_vr_employee_experience_observation.experience_code', '=','experience_vr.value');
                    })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_observation_type')), function ($join) {
                        $join->on('wg_customer_vr_employee_experience_observation.observation_type', '=','experience_scene_observation_type.value');
                    })
                    ->select(
                        "wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id",
                        "experience_vr.item as experience",
                        DB::raw("COUNT(experience_vr.item) AS total"),
                        "experience_scene_observation_type.item as obsType"
                    )
                    ->where("wg_customer_vr_employee.customer_id",$customerId->value)
                    ->whereRaw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') = ? ",[$selectedYear->value])
                    ->groupBy("experience_vr.item", "experience_scene_observation_type.item");

        $query->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=','wg_customer_vr_employee.id');
        })
        ->join(DB::raw("({$queryObs->toSql()}) AS obs"), function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'obs.customer_vr_employ_answer_experience_id');
        })
        ->mergeBindings($queryObs);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function observationsDetail($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_employee_answer_experience.id",
            "date" => "wg_customer_vr_employee_answer_experience.registration_date AS date",
            "experience" => "experience_vr.item AS experience",
            "observationType" => "experience_scene_observation_type.item AS observationType",
            "observation" => "wg_customer_vr_employee_experience_observation.observation_value AS observation",
            "documentNumber" => "wg_employee.documentNumber AS documentNumber",
            "firstName" => "wg_employee.firstName AS firstName",
            "lastName" => "wg_employee.lastName AS lastName",
            "selectedYear" => DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS selectedYear"),
            "customerId" => "wg_customer_vr_employee.customer_id"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->join("wg_customer_vr_employee_experience_observation", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.id', '=','wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.experience_code', '=','experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_observation_type')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.observation_type', '=','experience_scene_observation_type.value');
        })->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=','wg_customer_vr_employee.id');
        })->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
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

        $this->canSave($entityModel, $entity);

        $entityModel->id = $entity->id;
        $entityModel->customerVrEmployeeId = $entity->customerVrEmployeeId;
        $entityModel->registrationDate = Carbon::parse($entity->registrationDate)->timezone('America/Bogota');

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->createdAt = Carbon::now();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
        }
        
        $entityModel->save();

        $sceneAnswer = new SceneAnswerRepository();
        $entity->id = $entityModel->id;
        $sceneAnswer->insertOrUpdate($entity);

        if($entity->observationType) {
            $experienceObservation = new ExperienceObservationRepository();
            $experienceObservation->insertOrUpdate($entity);
        }

        // save progress percent log
        $this->saveLogExperienceAnswers($entity->customerVrEmployeeId, $entity->experienceCode);
    }

    private function canSave($entityModel, $entity)
    {
        if (empty($entityModel->id)) {
            $exists = DB::table('wg_customer_vr_employee as vr')
                ->join('wg_customer_vr_employee as vr2', 'vr2.customer_employee_id', '=', 'vr.customer_employee_id')
                ->join('wg_customer_vr_employee_answer_experience as exp', 'exp.customer_vr_employee_id', '=', 'vr2.id')
                ->where('vr.id', $entity->customerVrEmployeeId)
                ->where('exp.registration_date', Carbon::parse($entity->registrationDate)->timezone('America/Bogota'))
                ->exists();

            if ($exists) {
                throw new Exception('Ya existe un registro para la fecha dada.');
            }
        }
    }

    public function getQuestion($criteria)
    {
        return $this->service->getQuestion($criteria);
    }


    public static function getPeriodList($criteria) {
        $reposity = new self;
        $query = $reposity->query();
        $query->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=','wg_customer_vr_employee.id');
        })
        ->select(
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS item"),
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS value")
        )->groupBy(
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y')")
        )
        ->where("customer_employee_id", $criteria->employeeId)
        ->orderBy("wg_customer_vr_employee_answer_experience.registration_date", "desc");
        
        return $query->get();
    }

    public static function getPeriodObservationsList($criteria) {
        $reposity = new self;
        $query = $reposity->query();
        $query->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=','wg_customer_vr_employee.id');
        })
        ->join("wg_customer_vr_employee_experience_observation", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.id', '=','wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
        })
        ->select(
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS item"),
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS value")
        )->groupBy(
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y')")
        )
        ->where("customer_id", $criteria->customerId)
        ->orderBy("wg_customer_vr_employee_answer_experience.registration_date", "desc");
        

        return $query->get();
    }

    public static function getIndicatorsPeriodList($criteria) {
        $query = DB::table('wg_customer_vr_employee_experience_indicators');
        $query->select(
            "wg_customer_vr_employee_experience_indicators.period AS item",
            "wg_customer_vr_employee_experience_indicators.period AS value"
        )
        ->groupBy("wg_customer_vr_employee_experience_indicators.period")
        ->orderBy("wg_customer_vr_employee_experience_indicators.period", "desc");        
        $query->where("customer_id", $criteria->customerId);
        return $query->get();
    }

    public static function getPeriodObservationList() {
        $reposity = new self;
        $query = $reposity->query();
        $query->select(
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS item"),
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS value")
        )
        ->groupBy(
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y')")
        )
        ->orderBy("wg_customer_vr_employee_answer_experience.registration_date", "desc");

        return $query->get();
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->exportExcel($criteria);
        $filename = 'Detalle_VR_Empleado_Observaciones' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Registros', $data);
    }

    public static function getGenreChart($criteria)
    {
        $reposity = new self;
        return $reposity->service->getGenreChart($criteria);
    }

    public static function getObsTypesChart($criteria)
    {
        $reposity = new self;
        return $reposity->service->getObsTypesChart($criteria);
    }

    public static function getGenreIndicatorChart($criteria)
    {
        $reposity = new self;
        return $reposity->service->getGenreIndicatorChart($criteria);
    }

    public static function getCompetitorExperienceChart($criteria)
    {
        $reposity = new self;
        return $reposity->service->getCompetitorExperienceChart($criteria);
    }

    public static function getPeriodChart($criteria)
    {
        $reposity = new self;
        return $reposity->service->getPeriodChart($criteria);
    }

    public function getAllExperiencesWithScenes($criteria)
    {
        return $this->service->getAllExperiencesWithScenes($criteria);
    }

    public function saveLogExperienceAnswers(int $customerVrEmployeeId, string $experienceCode)
    {
        DB::table('wg_customer_vr_employee_experiences_progress_log')
            ->where('customer_vr_employee_id', $customerVrEmployeeId)
            ->where('experience_code', $experienceCode)
            ->delete();

        $select = DB::table('wg_customer_vr_employee_experience as emp_exp')
            ->join('wg_customer_vr_employee as vr', 'vr.id', '=', 'emp_exp.customer_vr_employee_id')
            ->leftJoin('wg_customer_vr_employee_answer_experience as answer_rv', 'answer_rv.customer_vr_employee_id', '=', 'emp_exp.customer_vr_employee_id')
            ->leftJoin('wg_customer_vr_employee_question_scene as question', 'question.experience_scene_code', '=', 'emp_exp.experience_scene_code')
            ->leftJoin('wg_customer_vr_employee_answer_scene as answer', function ($join) {
                $join->on('question.id', 'answer.customer_vr_employee_question_scene_id');
                $join->on('answer_rv.id', 'answer.customer_vr_employ_answer_experience_id');
            })
            ->where('vr.id', $customerVrEmployeeId)
            ->where('emp_exp.application', 'SI')
            ->where('emp_exp.experience_code', $experienceCode)
            ->groupBy('emp_exp.customer_vr_employee_id', 'emp_exp.experience_code')
            ->select(
                'emp_exp.customer_vr_employee_id',
                'emp_exp.id AS customer_vr_employee_experience',
                'emp_exp.experience_code',
                DB::raw("COUNT(question.id) AS questions"),
                DB::raw("COUNT(answer.id) AS answers"),
                DB::raw("COUNT(IF(answer.value = 'SI', answer.id, NULL)) as si"),
                DB::raw("COUNT(IF(answer.value = 'NO', answer.id, NULL)) as no"),
                DB::raw("COUNT(IF(answer.value = 'NA', answer.id, NULL)) as na"),
                DB::raw("IF(COUNT(answer.id) > 0,
                          COALESCE(
                                ROUND(
                                      COUNT(IF(answer.value = 'SI', 1, NULL)) /
                                      COUNT(IF(answer.value = 'SI' OR answer.value = 'NO', 1, NULL))
                                  , 2) * 100
                          , 0)
                       , 0) AS percent"),
                DB::raw("answer_rv.registration_date as created_at")
            );

        $sql = 'INSERT INTO wg_customer_vr_employee_experiences_progress_log ( 
                    `customer_vr_employee_id`, `customer_vr_employee_experience_id`, `experience_code`, 
                    `questions`, `answers`, `si`, `no`, `na`, `percent`, `created_at`) ' .
                $select->toSql();

        DB::statement($sql, $select->getBindings());
    }


    public function getExperienceByEmployeeIndicators(int $customerEmployeeId, int $year)
    {
        return $this->service->getExperienceByEmployeeIndicators($customerEmployeeId, $year);
    }

}