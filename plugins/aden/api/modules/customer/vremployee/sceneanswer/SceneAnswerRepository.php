<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\SceneAnswer;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class SceneAnswerRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new SceneAnswerModel());
        $this->service = new SceneAnswerService();
    }

    public function summary($criteria)
    {
        $this->setColumns([
            "experience" => "experience_vr.item AS experience",
            "scene" => "experience_scene.item AS scene",
            "question" => "wg_customer_vr_employee_question_scene.description AS question",
            "answer" => "experience_scene_application.item AS answer",
            "customerVrEmployeeId" => "wg_customer_vr_employee_experience.customer_vr_employee_id AS customerVrEmployeeId",
            "answerVal" => "experience_scene_application.value AS answerVal"
        ]);

        $this->parseCriteria($criteria);
        $query = DB::table('wg_customer_vr_employee_question_scene')
            ->join("wg_customer_vr_employee_experience", function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.experience_scene_code', '=','wg_customer_vr_employee_experience.experience_scene_code');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('wg_customer_vr_employee_experience.experience_code', '=','experience_vr.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.experience_scene_code', '=','experience_scene.value');
            })
            ->leftJoin("wg_customer_vr_employee_answer_experience", function ($join) {
                $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=','wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
            })
            ->leftJoin("wg_customer_vr_employee_answer_scene", function ($join) {
                $join->on('wg_customer_vr_employee_answer_experience.id', '=','wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id');
                $join->on('wg_customer_vr_employee_question_scene.id', '=','wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id');
            })
            ->leftJoin("wg_customer_vr_employee_experience_observation", function ($join) {
                $join->on('wg_customer_vr_employee_answer_experience.id', '=','wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
                $join->on('wg_customer_vr_employee_experience.experience_code', '=','wg_customer_vr_employee_experience_observation.experience_code');
            })
            ->leftJoin(DB::raw(SystemParameter::getRelationTable('experience_scene_application')), function ($join) {
                $join->on('wg_customer_vr_employee_answer_scene.value', '=','experience_scene_application.value');
            })
            ->where('wg_customer_vr_employee_experience.application',"SI")
            ->groupBy("wg_customer_vr_employee_question_scene.id");
        
        $query = $this->query($query);
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function allSummary($criteria)
    {
        $this->setColumns([
            "date" => "wg_customer_vr_employee_answer_experience.registration_date as date",
            "experience" => "experience_vr.item AS experience",
            "scene" => "experience_scene.item AS scene",
            "question" => "wg_customer_vr_employee_question_scene.description AS question",
            "answer" => "experience_scene_application.item AS answer",
            "customerEmployeeId" => "wg_customer_vr_employee.customer_employee_id AS customerEmployeeId",
            "selectedYear" =>  DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS selectedYear"),
            "customerId" => "wg_customer_vr_employee.customer_id AS customerId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->leftJoin("wg_customer_vr_employee_question_scene", function ($join) {
            $join->on('wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id', '=', 'wg_customer_vr_employee_question_scene.id');
        })->leftJoin("wg_customer_vr_employee_experience", function ($join) {
            $join->on('wg_customer_vr_employee_question_scene.experience_scene_code', '=','wg_customer_vr_employee_experience.experience_scene_code');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience.experience_code', '=','experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
            $join->on('wg_customer_vr_employee_experience.experience_scene_code', '=','experience_scene.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_application')), function ($join) {
            $join->on('wg_customer_vr_employee_answer_scene.value', '=','experience_scene_application.value');
        })->join("wg_customer_vr_employee_answer_experience", function ($join) {
            $join->on('wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id', '=','wg_customer_vr_employee_answer_experience.id');
            $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=','wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
        })->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=','wg_customer_vr_employee.id');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($sceneList)
    {

        foreach ($sceneList->questionList as $scene) {
            foreach ($scene->questions as $question) {
                if($question->answer) {
                    $isNewRecord = false;
                    $authUser = $this->getAuthUser();
                    if (!($entityModel = $this->find($question->answerId))) {
                        $entityModel = $this->model->newInstance();
                        $isNewRecord = true;
                    }
                    
                    $entityModel->id = $question->answerId;
                    $entityModel->customerVrEmployAnswerExperienceId = $sceneList->id;
                    $entityModel->customerVrEmployeeQuestionSceneId = $question->questionId;
                    $entityModel->value = $question->answer->value;
                    $entityModel->observation = $question->observation;
                    
                    if ($isNewRecord) {
                        $entityModel->createdBy = $authUser ? $authUser->id : 1;
                        $entityModel->createdAt = Carbon::now();
                    } else {
                        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
                        $entityModel->updatedAt = Carbon::now();
                    }
                    
                    $entityModel->save();
                }
            }
        }
    }


    public function getExperienceByEmployee($criteria)
    {
        $this->setColumns([
            "experienceCode" => "p.experience_code as experienceCode",
            "experience" => "experience_vr.item AS experience",
            "date" => DB::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d') AS date"),
            "percent" => "p.percent",
            "customerEmployeeId" => "vr.customer_employee_id AS customerEmployeeId",
            "year" =>  DB::raw("YEAR(p.created_at) as year")
        ]);

        $this->parseCriteria($criteria);

        $query = DB::table('wg_customer_vr_employee_experiences_progress_log as p')
            ->join('wg_customer_vr_employee as vr', function ($join) {
                $join->on('vr.id', 'p.customer_vr_employee_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('p.experience_code', '=','experience_vr.value');
            });

        $query = $this->query($query);
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

}