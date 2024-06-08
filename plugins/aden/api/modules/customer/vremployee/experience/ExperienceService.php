<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\Experience;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeRepository;
use DB;
use Illuminate\Support\Collection;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\SystemParameter\SystemParameter;

class ExperienceService extends BaseService
{
    public function getExperienceList($criteria, $sceneApplication)
    {
        $experienceClient = Collection::make(CustomerParameter::whereNamespace("wgroup")->whereGroup("experienceVR")->whereCustomerId($criteria->customerId)->get(["item"])->toArray());
        if (count($experienceClient)) {

            $query = DB::table('wg_customer_vr_employee_experience')
                ->rightJoin(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) use ($criteria) {
                    $join->on('wg_customer_vr_employee_experience.experience_scene_code', '=', 'experience_scene.value');
                    $join->where('wg_customer_vr_employee_experience.customer_vr_employee_id', "=", $criteria->vrEmployeeId);
                })
                ->leftJoin(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                    $join->on('experience_scene.code', '=', 'experience_vr.value');
                })
                ->whereIn("experience_vr.id", $experienceClient->flatten()->toArray())
                ->select(
                    'wg_customer_vr_employee_experience.id',
                    'application',
                    'justification',
                    'experience_vr.item as experience',
                    'experience_vr.value as experienceValue',
                    'experience_scene.item as scene',
                    'experience_scene.value as sceneValue'
                )
                ->orderBy("experience_vr.value")
                ->get()
                ->toArray();

            $data = [];
            $sceneApplication = $sceneApplication->keyBy("value");
            foreach ($query as $row) {
                $data[$row->experienceValue]["experience"] = $row->experience;
                if ($row->application) {
                    $row->application = $sceneApplication[$row->application];
                }
                $data[$row->experienceValue]["scenes"][] = $row;
            }

            return array_values($data);
        }

        return [];
    }

    public function getEmployeeExperienceFilterList($criteria)
    {
        return DB::table('wg_customer_vr_employee_experience')
            ->join("wg_customer_vr_employee", function ($join) {
                $join->on('wg_customer_vr_employee.id', '=', 'wg_customer_vr_employee_experience.customer_vr_employee_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('wg_customer_vr_employee_experience.experience_code', '=', 'experience_vr.value');
            })
            ->leftjoin("wg_customer_vr_employee_answer_experience", function ($join) use ($criteria) {
                $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
                // $join->where('wg_customer_vr_employee_answer_experience.registration_date' ,"=", Carbon::parse($criteria->registrationDate)->toDateString());
            })
            ->leftjoin("wg_customer_vr_employee_question_scene", function ($join) {
                $join->on('wg_customer_vr_employee_experience.experience_scene_code', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
            })
            ->leftjoin("wg_customer_vr_employee_answer_scene", function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id');
                $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id');
            })
            ->leftJoin('wg_customer_vr_employee_experiences_progress_log as percent_1', function ($join) {
                $join->on('percent_1.customer_vr_employee_id', 'wg_customer_vr_employee_experience.customer_vr_employee_id');
                $join->on('percent_1.experience_code', 'wg_customer_vr_employee_experience.experience_code');
            })
            ->leftJoin('wg_customer_vr_employee_experiences_progress_log as percent_2', function ($join) {
                $join->on('percent_2.customer_vr_employee_id', 'percent_1.customer_vr_employee_id');
                $join->on('percent_2.experience_code', 'percent_1.experience_code');
                $join->whereRaw('percent_1.created_at < percent_2.created_at');
            })
            ->when(!empty($criteria->cvreid), function ($query) use ($criteria) {
                $query->where('wg_customer_vr_employee_experience.customer_vr_employee_id', $criteria->cvreid);
            })
            ->when(!empty($criteria->customerId), function ($query) use ($criteria) {
                $query->where('wg_customer_vr_employee.customer_id', $criteria->customerId);
            })
            ->when(!empty($criteria->period), function ($query) use ($criteria) {
                $query->whereYear('wg_customer_vr_employee_answer_experience.registration_date', $criteria->period);
            })            
            ->where('wg_customer_vr_employee_experience.application', "SI")
            ->whereNull('percent_2.id')
            ->select(
                'experience_vr.item as item',
                'experience_vr.value as value'
            )
            ->groupBy(
                "experience_vr.value"
            )
            ->orderBy("experience_vr.value")
            ->get();
    }

    public function getEmployeeExperienceQuery($criteria)
    {
        return DB::table('wg_customer_vr_employee_experience')
            ->join("wg_customer_vr_employee", function ($join) {
                $join->on('wg_customer_vr_employee.id', '=', 'wg_customer_vr_employee_experience.customer_vr_employee_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('wg_customer_vr_employee_experience.experience_code', '=', 'experience_vr.value');
            })
            ->leftjoin("wg_customer_vr_employee_answer_experience", function ($join) use ($criteria) {
                $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
                // $join->where('wg_customer_vr_employee_answer_experience.registration_date' ,"=", Carbon::parse($criteria->registrationDate)->toDateString());
            })
            ->leftjoin("wg_customer_vr_employee_question_scene", function ($join) {
                $join->on('wg_customer_vr_employee_experience.experience_scene_code', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
            })
            ->leftjoin("wg_customer_vr_employee_answer_scene", function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id');
                $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id');
            })
            ->leftJoin('wg_customer_vr_employee_experiences_progress_log as percent_1', function ($join) {
                $join->on('percent_1.customer_vr_employee_id', 'wg_customer_vr_employee_experience.customer_vr_employee_id');
                $join->on('percent_1.experience_code', 'wg_customer_vr_employee_experience.experience_code');
            })
            ->leftJoin('wg_customer_vr_employee_experiences_progress_log as percent_2', function ($join) {
                $join->on('percent_2.customer_vr_employee_id', 'percent_1.customer_vr_employee_id');
                $join->on('percent_2.experience_code', 'percent_1.experience_code');
                $join->whereRaw('percent_1.created_at < percent_2.created_at');
            })
            ->when(!empty($criteria->cvreid), function ($query) use ($criteria) {
                $query->where('wg_customer_vr_employee_experience.customer_vr_employee_id', $criteria->cvreid);
            })
            ->when(!empty($criteria->customerId), function ($query) use ($criteria) {
                $query->where('wg_customer_vr_employee.customer_id', $criteria->customerId);
            })
            ->when(!empty($criteria->period), function ($query) use ($criteria) {
                $query->whereYear('wg_customer_vr_employee_answer_experience.registration_date', $criteria->period);
            })            
            ->where('wg_customer_vr_employee_experience.application', "SI")
            ->whereNull('percent_2.id')
            ->select(
                'wg_customer_vr_employee_answer_experience.id',
                'wg_customer_vr_employee_answer_experience.registration_date',
                'wg_customer_vr_employee.customer_id',
                'wg_customer_vr_employee_experience.customer_vr_employee_id',
                'wg_customer_vr_employee_experience.id AS customer_vr_employee_experience_id',
                'experience_vr.item as experience',
                'experience_vr.value as experienceValue',
                DB::raw("COUNT(wg_customer_vr_employee_question_scene.id) as questions"),
                DB::raw("COUNT(wg_customer_vr_employee_answer_scene.id) as answers"),
                DB::raw("COALESCE( percent_1.percent, 0 ) AS percent"),
                DB::raw("CASE WHEN percent_1.percent >= 80 THEN 'text-green'
                          WHEN percent_1.percent >= 60 THEN 'text-yellow'
                          ELSE 'text-red' END as color_percent"),
                'percent_1.si as si',
                'percent_1.no as no'
            )
            ->groupBy(
                "wg_customer_vr_employee_experience.customer_vr_employee_id",
                "experience_vr.value"
            )
            ->orderBy("experience_vr.value");
    }

    public function getEmployeeExperienceList($criteria)
    {
        $data = $this->getEmployeeExperienceQuery($criteria)
            ->get();

        for ($i = 0; $i < count($data); $i++) {
            if ($i == 0) {
                $data[$i]->isActive = true;
            } else {
                if ($data[$i]->answers == $data[$i]->questions) {
                    $data[$i]->isActive = true;
                } elseif ($data[$i - 1]->answers == $data[$i - 1]->questions) {
                    $data[$i]->isActive = true;
                }
            }

            $dataChart = [
                (object) ['label' => 'SI', 'value' => $data[$i]->si, 'color' => '#5bc01e'],
                (object) ['label' => 'NO', 'value' => $data[$i]->no, 'color' => '#eea236'],
            ];

            $data[$i]->chartPieData = $this->chart->getChartPie($dataChart);
        }

        return $data;
    }

    public function getEmployeeExperiencePeriodList($criteria)
    {
        $query = $this->getEmployeeExperienceQuery($criteria);

        $data = DB::table(DB::raw("({$query->toSql()}) as customer_employee_vr_experience"))
            ->mergeBindings($query)
            ->whereNotNull('customer_employee_vr_experience.id')
            ->select(
                DB::raw("YEAR(customer_employee_vr_experience.registration_date) AS item"),
                DB::raw("YEAR(customer_employee_vr_experience.registration_date) AS value"),
                DB::raw("YEAR(customer_employee_vr_experience.registration_date) AS name")
            )
            ->groupBy(DB::raw("YEAR(customer_employee_vr_experience.registration_date)"))
            ->orderBy(DB::raw("YEAR(customer_employee_vr_experience.registration_date)"), 'DESC')
            ->get();

        return $data;
    }

    public function getStats($criteria)
    {
        $data = DB::table('wg_customer_vr_employee_experience')
            ->leftjoin("wg_customer_vr_employee_answer_experience", function ($join) use ($criteria) {
                $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
                // $join->where('wg_customer_vr_employee_answer_experience.registration_date' ,"=", Carbon::parse($criteria->registrationDate)->toDateString());
            })
            ->leftjoin("wg_customer_vr_employee_question_scene", function ($join) {
                $join->on('wg_customer_vr_employee_experience.experience_scene_code', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
            })
            ->leftjoin("wg_customer_vr_employee_answer_scene", function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id');
                $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id');
            })
            ->where('wg_customer_vr_employee_experience.customer_vr_employee_id', $criteria->cvreid)
            ->where("wg_customer_vr_employee_experience.application", "SI")
            ->select(
                DB::raw("COUNT(wg_customer_vr_employee_question_scene.id) as questions"),
                DB::raw("COUNT(wg_customer_vr_employee_answer_scene.id) as answers")
            )
            ->first();

        $data->percentage = $data->answers > 0 ? number_format(($data->answers / $data->questions) * 100, 0) : 0;
        if ($data->percentage > 0) {
            CustomerVrEmployeeRepository::setAverage($criteria->cvreid, $data->percentage);
        }

        return $data;
    }


    public static function getExperiencesWithAnswers(int $customerVrEmployeeId): Collection
    {
        return DB::table('wg_customer_vr_employee_experience as emp_exp')
            ->leftJoin(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('experience_vr.value', '=', 'emp_exp.experience_code');
            })
            ->join('wg_customer_vr_employee_answer_experience as answer_rv', 'answer_rv.customer_vr_employee_id', '=', 'emp_exp.customer_vr_employee_id')
            ->join('wg_customer_vr_employee_question_scene as question', 'question.experience_scene_code', '=', 'emp_exp.experience_scene_code')
            ->join('wg_customer_vr_employee_answer_scene as answer', function ($join) {
                $join->on('answer.customer_vr_employee_question_scene_id',  'question.id');
                $join->on('answer.customer_vr_employ_answer_experience_id', 'answer_rv.id');
            })
            ->where('emp_exp.application', 'SI')
            ->where('emp_exp.customer_vr_employee_id', $customerVrEmployeeId)
            ->groupBy('emp_exp.experience_code')
            ->havingRaw("COUNT(answer.id) > 0")
            ->select(
                DB::raw('UPPER(experience_vr.item) as experience')
            )
            ->get();
    }
}
