<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\ExportHelper;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\SystemParameter\SystemParameter;

class ExperienceAnswerService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getQuestion($criteria)
    {

        $query = DB::table('wg_customer_vr_employee_question_scene')
            ->join("wg_customer_vr_employee_experience", function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.experience_scene_code', '=', 'wg_customer_vr_employee_experience.experience_scene_code');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
                $join->on('wg_customer_vr_employee_question_scene.experience_scene_code', '=', 'experience_scene.value');
            })
            ->leftJoin("wg_customer_vr_employee_answer_experience", function ($join) use ($criteria) {
                $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
                if ($criteria->id > 0) {
                    $join->where('wg_customer_vr_employee_answer_experience.id', "=", $criteria->id);
                }
            })
            ->leftJoin("wg_customer_vr_employee_answer_scene", function ($join) {
                $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id');
                $join->on('wg_customer_vr_employee_question_scene.id', '=', 'wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id');
            })
            ->leftJoin("wg_customer_vr_employee_experience_observation", function ($join) {
                $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
                $join->on('wg_customer_vr_employee_experience.experience_code', '=', 'wg_customer_vr_employee_experience_observation.experience_code');
            })
            ->where('wg_customer_vr_employee_experience.experience_code', $criteria->experienceCode)
            ->where('wg_customer_vr_employee_experience.application', "SI")
            ->where('wg_customer_vr_employee_experience.customer_vr_employee_id', $criteria->customerVrEmployeeId)
            ->select(
                'wg_customer_vr_employee_answer_experience.id',
                'wg_customer_vr_employee_answer_scene.id as answerId',
                'wg_customer_vr_employee_answer_scene.value as answer',
                'wg_customer_vr_employee_answer_scene.observation',
                'wg_customer_vr_employee_question_scene.description',
                'wg_customer_vr_employee_question_scene.id as questionId',
                'wg_customer_vr_employee_experience_observation.observation_type',
                'wg_customer_vr_employee_experience_observation.observation_value',
                'experience_scene.item as scene',
                'experience_scene.value as codeScene',
                DB::raw("SUM(IF(wg_customer_vr_employee_answer_scene.value is not null, 1 ,0)) as answers")
            )
            ->groupBy("wg_customer_vr_employee_question_scene.id")
            ->orderBy("wg_customer_vr_employee_experience.experience_code")
            ->get();

        $options = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene_application")
            ->select("id", "item", "value")
            ->get();

        $options2 = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene_observation_type")
            ->select("id", "item", "value")
            ->get();

        $sceneApplication = $options->keyBy("value");
        $observationOptions = $options2->keyBy("value");
        $data = [];
        $observation = null;
        $criteria->answers = 0;
        foreach ($query as $row) {

            $criteria->answers += $row->answers;
            $criteria->id = $row->id;

            if ($row->answer) {
                $row->answer = isset($sceneApplication[$row->answer]) ? $sceneApplication[$row->answer] : null;
            }

            if (is_null($observation) && $row->observation_type) {
                $observation = ["type" => $row->observation_type, "value" => $row->observation_value];
            }

            $data[$row->codeScene]["scene"] = $row->scene;
            $data[$row->codeScene]["questions"][] = $row;
        }

        $criteria->questionList = $data;
        $criteria->observationType = $observation ? $observationOptions[$observation["type"]] : null;
        $criteria->observationValue = $observation ? $observation["value"] : null;
        return $criteria;
    }

    public function exportExcel($criteria)
    {

        $query = DB::table('wg_customer_vr_employee_answer_experience');
        $query->join("wg_customer_vr_employee_experience_observation", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.experience_code', '=', 'experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_observation_type')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.observation_type', '=', 'experience_scene_observation_type.value');
        })->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
        })->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
        });


        $query->select(
            "wg_customer_vr_employee_answer_experience.registration_date AS date",
            "experience_vr.item AS experience",
            "experience_scene_observation_type.item AS observationType",
            "wg_customer_vr_employee_experience_observation.observation_value AS observation",
            "wg_employee.documentNumber AS documentNumber",
            "wg_employee.firstName AS firstName",
            "wg_employee.lastName AS lastName",
            "wg_customer_vr_employee.customer_id as customerId",
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS selectedYear")
        );

        $query = $this->prepareQuery($query->toSql())
            ->mergeBindings($query);

        $this->applyWhere($query, $criteria);

        return ExportHelper::headings($query->get(), $this->getHeader());
    }

    public function getHeader()
    {
        return  [
            "FECHA" => "date",
            "EXPERIENCIA" => "experience",
            "TIPO OBSERVACIÓN" => "observationType",
            "OBSERVACIÓN" => "observation",
            "NÚMERO IDENTIFICACIÓN" => "documentNumber",
            "NOMBRE(S)" => "firstName",
            "APELLIDOS" => "lastName"
        ];
    }


    public function getGenreChart($criteria)
    {

        $query = DB::table('wg_customer_vr_employee_answer_experience');
        $query->join("wg_customer_vr_employee_experience_observation", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.experience_code', '=', 'experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_observation_type')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_observation.observation_type', '=', 'experience_scene_observation_type.value');
        })->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
        })->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
        })
            ->whereRaw("wg_employee.gender IS NOT NULL")
            ->select(
                DB::raw("IF(wg_employee.gender='F','Mujeres','Hombres') as label")
            )
            ->where("wg_customer_vr_employee.customer_id", $criteria->customerId);
        // ->groupBy("wg_customer_vr_employee.customer_employee_id");

        if (!empty($criteria->selectedYear)) {
            $query->whereRaw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') = {$criteria->selectedYear}");
        } else {
            return [[], 0];
        }

        $query = DB::table(DB::raw("({$query->toSql()}) AS p"))
            ->mergeBindings($query)
            ->select(
                "p.label",
                DB::raw("COUNT(p.label ) AS value")
            )
            ->groupBy("p.label")
            ->limit(2);

        $data = $query->get();
        $total = 0;
        foreach ($data as $value) {
            $total += $value->value;
        }

        return [$this->chart->getChartPie($data), $total];
    }

    public function getObsTypesChart($criteria)
    {
        $query = DB::table('wg_customer_vr_employee_answer_experience');
        $query->join("wg_customer_vr_employee_experience_observation", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.id', '=', 'wg_customer_vr_employee_experience_observation.customer_vr_employ_answer_experience_id');
        })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('wg_customer_vr_employee_experience_observation.experience_code', '=', 'experience_vr.value');
            })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_observation_type')), function ($join) {
                $join->on('wg_customer_vr_employee_experience_observation.observation_type', '=', 'experience_scene_observation_type.value');
            })->join("wg_customer_vr_employee", function ($join) {
                $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
            })
            ->where("wg_customer_vr_employee.customer_id", $criteria->customerId)
            ->select(
                "experience_scene_observation_type.item as label"
            );
        // ->groupBy("wg_customer_vr_employee.customer_employee_id");

        if (!empty($criteria->selectedYear)) {
            $query->whereRaw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') = {$criteria->selectedYear}");
        } else {
            return [];
        }

        $query = DB::table(DB::raw("({$query->toSql()}) AS p"))
            ->mergeBindings($query)
            ->select(
                "p.label",
                DB::raw("COUNT(p.label ) AS value")
            )
            ->groupBy("p.label");

        $data = $query->get();
        $config = array(
            "labelColumn" => ["Número de usuarios por Tipo de Observación"],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }


    public function getGenreIndicatorChart($criteria)
    {

        $query = DB::table('wg_customer_vr_employee_experience_indicators')
            ->where("customer_id", $criteria->customerId)
            ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
                $query->where('experience_code', $criteria->selectedExperience);
            })
            ->when(!empty($criteria->selectedDateRange), function ($query) use ($criteria) {
                $query->whereBetween('registrationDate', [$criteria->selectedDateRange->startDate, $criteria->selectedDateRange->endDate]);
            });



        if (!empty($criteria->selectedYear)) {
            $query->where("period", $criteria->selectedYear);
        } else {
            return [[], 0];
        }

        $data = $query->select("female", "male")->first();
        $female = $data && isset($data->female) ? $data->female : 0;
        $male = $data && isset($data->male) ? $data->male : 0;
        $result[] = (object)["label" => "Mujeres", "value" => $female];
        $result[] = (object)["label" => "Hombres", "value" => $male];
        $total = $female + $male;

        return [$this->chart->getChartPie($result), $total];
    }

    public function getCompetitorExperienceChart($criteria)
    {
        $query = DB::table('wg_customer_vr_employee_experience_indicators');
        $query->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_code', '=', 'experience_vr.value');
        });

        $query->where("wg_customer_vr_employee_experience_indicators.customer_id", $criteria->customerId)
            ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
                $query->where('wg_customer_vr_employee_experience_indicators.experience_code', $criteria->selectedExperience);
            })
            ->when(!empty($criteria->selectedDateRange), function ($query) use ($criteria) {
                $query->whereBetween('wg_customer_vr_employee_experience_indicators.registrationDate', [$criteria->selectedDateRange->startDate, $criteria->selectedDateRange->endDate]);
            });

        if (!empty($criteria->selectedYear)) {
            $query->where("period", $criteria->selectedYear);
        } else {
            return [];
        }

        $query->select(
            "experience_vr.item as label",
            "wg_customer_vr_employee_experience_indicators.participants AS value"
        )
            ->groupBy("monthNumber", "experience_vr.item");

        $data = [];
        foreach ($query->get() as $experience) {
            $data[$experience->label]["label"] = $experience->label;
            if (!empty($data[$experience->label]["value"])) {
                $data[$experience->label]["value"] += $experience->value;
            } else {
                $data[$experience->label]["value"] = $experience->value;;
            }
        }

        foreach ($data as $experience => $value) {
            $data[$experience] = (object)$value;
        }


        $config = array(
            "labelColumn" => ["Participantes por Experiencia"],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getAllExperiencesWithScenes($criteria)
    {
        $query = DB::table('wg_customer_vr_employee_experience_indicators');
        $query->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_code', '=', 'experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_scene_code', '=', 'experience_scene.value');
        })->join("wg_customer_vr_employee_question_scene", function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.question_id', '=', 'wg_customer_vr_employee_question_scene.id');
            $join->on('wg_customer_vr_employee_experience_indicators.experience_scene_code', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
        });

        $query
            ->select(
                "experience_vr.item AS experience",
                "experience_scene.item AS scene",
                DB::raw("SUM(wg_customer_vr_employee_experience_indicators.positiveAnswers) AS positiveAnswers"),
                DB::raw("SUM(wg_customer_vr_employee_experience_indicators.questions) as questions"),
                "wg_customer_vr_employee_question_scene.description as question"
            )
            ->groupBy("customer_id", "period", "experience_vr.item", "experience_scene.item", "wg_customer_vr_employee_question_scene.description");

        $query->where("wg_customer_vr_employee_experience_indicators.customer_id", $criteria->customerId)
            // ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
            //     $query->where('wg_customer_vr_employee_experience_indicators.experience_code', $criteria->selectedExperience->value);
            // })
            // ->when(!empty($criteria->selectedRangeDates), function ($query) use ($criteria) {
            //     $query->whereBetween('wg_customer_vr_employee_experience_indicators.registrationDate', [$criteria->selectedRangeDates->startDate, $criteria->selectedRangeDates->endDate]);
            // })
            ;

        if (!empty($criteria->selectedYear)) {
            $query->where("period", $criteria->selectedYear->value);
        } else {
            return [];
        }

        $data = [];
        foreach ($query->get() as $row) {
            $data[$row->experience]["experience"] = $row->experience;
            $data[$row->experience]["scenes"][] = $row;
        }

        foreach ($data as $key => $experience) {
            $groupScenes = [];
            foreach ($experience["scenes"] as $scene) {
                $groupScenes[$scene->scene]["scene"] = $scene->scene;
                $groupScenes[$scene->scene]["questions"][] = $scene;
            }
            $data[$key]["scenes"] = $groupScenes;
        }

        foreach ($data as $key1 => $experience) {
            foreach ($experience["scenes"] as $key2 => $scene) {
                $groupQuestions = [];
                foreach ($scene["questions"] as $question) {
                    $groupQuestions[$question->question]["question"] = $question->question;
                    if ($question->positiveAnswers > 0) {
                        $value = number_format(($question->positiveAnswers / $question->questions) * 100, 0);
                    } else {
                        $value = 0;
                    }
                    $groupQuestions[$question->question]["values"] = ["percentage" => $value, "max" => $question->questions];
                }

                $data[$key1]["scenes"][$key2]["questions"] = $groupQuestions;
            }
        }

        return array_values($data);
    }

    public function getPeriodChart($criteria)
    {

        $query = DB::table('wg_customer_vr_employee_experience_indicators');
        $query->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_code', '=', 'experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_scene_code', '=', 'experience_scene.value');
        })->join("wg_customer_vr_employee_question_scene", function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.question_id', '=', 'wg_customer_vr_employee_question_scene.id');
            $join->on('wg_customer_vr_employee_experience_indicators.experience_scene_code', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
        });

        $query->select(
            DB::raw("CASE WHEN monthNumber = 1 THEN positiveAnswers END 'JAN'"),
            DB::raw("CASE WHEN monthNumber = 2 THEN positiveAnswers END 'FEB'"),
            DB::raw("CASE WHEN monthNumber = 3 THEN positiveAnswers END 'MAR'"),
            DB::raw("CASE WHEN monthNumber = 4 THEN positiveAnswers END 'APR'"),
            DB::raw("CASE WHEN monthNumber = 5 THEN positiveAnswers END 'MAY'"),
            DB::raw("CASE WHEN monthNumber = 6 THEN positiveAnswers END 'JUN'"),
            DB::raw("CASE WHEN monthNumber = 7 THEN positiveAnswers END 'JUL'"),
            DB::raw("CASE WHEN monthNumber = 8 THEN positiveAnswers END 'AUG'"),
            DB::raw("CASE WHEN monthNumber = 9 THEN positiveAnswers END 'SEP'"),
            DB::raw("CASE WHEN monthNumber = 10 THEN positiveAnswers END 'OCT'"),
            DB::raw("CASE WHEN monthNumber = 11 THEN positiveAnswers END 'NOV'"),
            DB::raw("CASE WHEN monthNumber = 12 THEN positiveAnswers END 'DEC'"),
            "period as label",
            "description as metric",
            "monthNumber"
        );

        $query->where("customer_id", $criteria->customerId)
            ->where("wg_customer_vr_employee_experience_indicators.experience_scene_code", $criteria->scene);

        if (!empty($criteria->period)) {
            $query->whereIn("period", $criteria->period);
        } else {
            return [];
        }

        $query->groupBy(
            'label',
            'monthNumber',
            'metric'
        )
            ->orderBy('label', 'DESC');

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        $filter = [];
        foreach ($query->get() as $metric) {
            if (!isset($filter[$metric->label][$metric->metric]["chart"])) {
                $filter[$metric->label][$metric->metric]["chart"] = $metric;
            } else {
                (int)$filter[$metric->label][$metric->metric]["chart"]->JAN += (int)$metric->JAN;
                (int)$filter[$metric->label][$metric->metric]["chart"]->FEB += (int)$metric->FEB;
                (int)$filter[$metric->label][$metric->metric]["chart"]->MAR += (int)$metric->MAR;
                (int)$filter[$metric->label][$metric->metric]["chart"]->APR += (int)$metric->APR;
                (int)$filter[$metric->label][$metric->metric]["chart"]->MAY += (int)$metric->MAY;
                (int)$filter[$metric->label][$metric->metric]["chart"]->JUN += (int)$metric->JUN;
                (int)$filter[$metric->label][$metric->metric]["chart"]->JUL += (int)$metric->JUL;
                (int)$filter[$metric->label][$metric->metric]["chart"]->AUG += (int)$metric->AUG;
                (int)$filter[$metric->label][$metric->metric]["chart"]->SEP += (int)$metric->SEP;
                (int)$filter[$metric->label][$metric->metric]["chart"]->OCT += (int)$metric->OCT;
                (int)$filter[$metric->label][$metric->metric]["chart"]->NOV += (int)$metric->NOV;
                (int)$filter[$metric->label][$metric->metric]["chart"]->DEC += (int)$metric->DEC;
            }
        }

        $data = [];
        foreach ($filter as $metrics) {
            foreach ($metrics as $chart) {
                $data[$chart["chart"]->metric]["metric"] = $chart["chart"]->metric;
                $data[$chart["chart"]->metric]["chart"][] = $chart["chart"];
            }
        }

        $data = array_values($data);
        foreach ($data as $key => $metric) {
            $data[$key]["chart"] = current([$this->chart->getChartLine($metric["chart"], $config)]);
        }

        return $data;
    }

    public function getExperienceByEmployeeIndicators(int $customerEmployeeId, int $year)
    {
        $result = [];

        $subquery = DB::table('wg_customer_vr_employee_experiences_progress_log as p')
            ->join('wg_customer_vr_employee as vr', function ($join) use ($customerEmployeeId) {
                $join->on('vr.id', 'p.customer_vr_employee_id');
                $join->whereRaw("vr.customer_employee_id = $customerEmployeeId");
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('p.experience_code', '=', 'experience_vr.value');
            })
            ->whereYear('p.created_at', $year)
            ->orderBy('experience_vr.item')
            ->orderBy('p.created_at')
            ->select(
                'p.experience_code',
                'experience_vr.item as label',
                DB::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d') AS dynamicColumn"),
                'p.percent as total'
            );

        $subquery->get()
            ->groupBy('label')
            ->map(function ($item, $key) use (&$result) {
                $temp = new \stdClass();

                $valueColumns = [];
                foreach ($item as $index => $row) {
                    if ($index == 0) {
                        $temp->experienceCode = $row->experience_code;
                        $valueColumns[] = ['label' => $row->label, 'field' => 'total'];
                    }
                }

                $config = array(
                    "labelColumn" => 'dynamicColumn', "valueColumns" => $valueColumns
                );

                $temp->experience = $key;
                $temp->chart = $this->chart->getChartLine($item, $config);
                $result[] = $temp;

                return $item;
            });

        return $result;
    }
}
