<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators;

use AdeN\Api\Classes\BaseService;
use Illuminate\Support\Facades\DB;
use October\Rain\Support\Collection;

use AdeN\Api\Modules\Customer\VrEmployee\Models\SatisfactionResponseModel;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\SystemParameter\SystemParameter;


class SatisfactionIndicatorService extends BaseService
{
    public function getAnswerTypes(int $customerId): Collection
    {
        return DB::table('wg_customer_vr_satisfactions_answers_types as at')
            ->join('wg_customer_vr_satisfactions_questions as q', 'q.answer_type', '=', 'at.code')
            ->leftjoin(DB::raw(CustomerParameter::getRelationTable('experienceVR', 'cp')), function ($join) use ($customerId) {
                $join->where('cp.customer_id', $customerId);
            })
            ->orderBy('cp.value')
            ->orderBy('q.label')
            ->orderBy('at.order')
            ->select(
                'at.answer as NOMBRE',
                DB::raw("CONCAT(cp.value, ' - ', q.label) as question")
            )
            ->where('isActive', 1)
            ->get()
            ->groupBy('question');
    }


    public function getChartLineRegisteredVsParticipants($customerId)
    {

        $participants = DB::table('wg_customer_vr_employee as e')
            ->join('wg_customer_vr_employee_answer_experience as ae', 'ae.customer_vr_employee_id', '=', 'e.id')
            ->whereNotNull('ae.registration_date')
            ->where('e.customer_id', $customerId)
            ->groupBy(DB::raw("YEAR(ae.registration_date)"))
            ->select(
                DB::raw("'Participantes' AS label"),
                DB::raw("YEAR(ae.registration_date) AS year"),
                DB::raw("count(customer_employee_id) AS total")
            );

        $encuestas = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->where('r.customer_id', $customerId)
            ->groupBy(DB::raw("YEAR(r.date_register)"))
            ->select(
                DB::raw("'Encuestas' AS label"),
                DB::raw("YEAR(r.date_register) AS year"),
                DB::raw("count(DISTINCT `group`) AS total")
            );


        $subquery = $participants->unionAll($encuestas);
        $data = $subquery->get();

        $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.year')
            ->select('d.year as label');


        $labels = [];
        $valueColumns = [];

        foreach ($data as $datum) {
            if (in_array($datum->label, $labels)) {
                continue;
            }

            $labels[] = $datum->label;
            $valueColumns[] = ['label' => $datum->label, 'field' => $datum->label];

            $query->addSelect(
                DB::raw("SUM(CASE WHEN label = '{$datum->label}' THEN total ELSE 0 END) AS '{$datum->label}'")
            );
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartBarAmountBySatisfaction($customerId, $startDate = null, $endDate = null)
    {
        $defaultQuestion = DB::table('system_parameters')
            ->where('group', 'vr_employee_satisfaction_chart')
            ->where('value', 'question')
            ->first();

        $question = DB::table('wg_customer_vr_satisfactions_questions')
            ->when($defaultQuestion, function ($query) use ($defaultQuestion) {
                $query->where('id', $defaultQuestion->item);
            })
            ->first();

        $answerTypes = DB::table('wg_customer_vr_satisfactions_answers_types')
            ->when($question, function ($query) use ($question) {
                $query->where('code', $question->answer_type);
            })
            ->get();

        $veryBad = $answerTypes->firstWhere('id', 1);
        $bad = $answerTypes->firstWhere('id', 2);
        $regular = $answerTypes->firstWhere('id', 3);
        $good = $answerTypes->firstWhere('id', 4);
        $veryGood = $answerTypes->firstWhere('id', 5);

        $query = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->join('wg_customer_vr_satisfactions_questions as q', function ($join) {
                $join->on('q.id', 'r.question_id');
                $join->where('q.label', 'Calificación Exp.');
            })
            ->join('wg_customer_parameter as cp', function ($join) {
                $join->on('cp.customer_id', 'r.customer_id');
                $join->where('cp.namespace', 'wgroup');
                $join->where('cp.group', 'experienceVR');
                $join->whereRaw('cp.item COLLATE utf8_general_ci = r.experience');
            })
            ->when($customerId, function ($query) use ($customerId) {
                $query->where('r.customer_id', $customerId);
            })
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('r.date_register', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('r.date_register', '<=', $endDate);
            })
            ->when($question, function ($query) use ($question) {
                $query->where('q.id', $question->id);
            })
            ->groupBy('r.experience')
            ->select(
                'cp.value as label',
                DB::raw("COUNT(IF(r.answer_id = 1, 1, NULL)) AS muy_malo"),
                DB::raw("COUNT(IF(r.answer_id = 2, 1, NULL)) AS malo"),
                DB::raw("COUNT(IF(r.answer_id = 3, 1, NULL)) AS regular"),
                DB::raw("COUNT(IF(r.answer_id = 4, 1, NULL)) AS bueno"),
                DB::raw("COUNT(IF(r.answer_id = 5, 1, NULL)) AS excelente")
            );

        $valueColumns = [];

        if ($veryBad &&  $veryBad->isActive == 1) {
            $valueColumns[] = ['label' => $veryBad ? $veryBad->answer : 'Muy malo', 'field' => 'muy_malo', 'color' => '#cb3434'];
        }

        if ($bad &&  $bad->isActive == 1) {
            $valueColumns[] = ['label' => $bad ? $bad->answer : 'Malo', 'field' => 'malo', 'color' => '#ff0000'];
        }

        if ($regular &&  $regular->isActive == 1) {
            $valueColumns[] = ['label' => $regular ? $regular->answer : 'Regular', 'field' => 'regular', 'color' => '#ff7f27'];
        }

        if ($good &&  $good->isActive == 1) {
            $valueColumns[] = ['label' => $good ? $good->answer :  'Bueno', 'field' => 'bueno', 'color' => '#b5e61d'];
        }

        if ($veryGood &&  $veryGood->isActive == 1) {
            $valueColumns[] = ['label' => $veryGood ? $veryGood->answer : 'Excelente', 'field' => 'excelente', 'color' => '#22b14c'];
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns,
        );

        return $this->chart->getChartBar($query->get(), $config);
    }


    public function getAllYears($customerId)
    {
        return SatisfactionResponseModel::query()
            ->where('customer_id', $customerId)
            ->groupBy(DB::raw('year(date_register)'))
            ->orderBy('date_register', 'desc')
            ->select(DB::raw('year(date_register) as year'))
            ->get()
            ->toArray();
    }

    public function getAllAnswerTypes()
    {
        $defaultQuestion = DB::table('system_parameters')
            ->where('group', 'vr_employee_satisfaction_chart')
            ->where('value', 'question')
            ->first();

        $question = DB::table('wg_customer_vr_satisfactions_questions')
            ->when($defaultQuestion, function ($query) use ($defaultQuestion) {
                $query->where('id', $defaultQuestion->item);
            })
            ->first();

        $answerTypes = DB::table('wg_customer_vr_satisfactions_answers_types')
            ->when($question, function ($query) use ($question) {
                $query->where('code', $question->answer_type);
            })
            ->get();

        $veryBad = $answerTypes->firstWhere('id', 1);
        $bad = $answerTypes->firstWhere('id', 2);
        $regular = $answerTypes->firstWhere('id', 3);
        $good = $answerTypes->firstWhere('id', 4);
        $veryGood = $answerTypes->firstWhere('id', 5);

        return [
            'veryBad' => $veryBad,
            'bad' => $bad,
            'regular' => $regular,
            'good' => $good,
            'veryGood' => $veryGood,
        ];
    }

    public function getChartBarQuestionVsResponses(int $customerId, string $date)
    {
        $subQuery = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->leftJoin(DB::raw(CustomerParameter::getRelationTable('experienceVR', 'cp')), function ($join) {
                $join->on('cp.customer_id', 'r.customer_id');
                $join->on('cp.item', '=', 'r.experience');
            })
            ->join('wg_customer_vr_satisfactions_questions as q', 'q.id', '=', 'r.question_id')
            ->join('wg_customer_vr_satisfactions_answers_types as ans', 'ans.code', '=', 'q.answer_type')
            ->where('r.customer_id', $customerId)
            ->whereRaw("DATE_FORMAT(r.date_register, '%Y-%m-%d') = '$date' ")
            ->whereRaw("ans.isActive = 1")
            ->groupBy('r.experience', 'q.label', 'ans.id')
            ->orderBy('ans.order')
            ->select(
                'cp.value as experience',
                "q.label",
                'ans.answer',
                DB::raw("COUNT(IF(r.answer_id = ans.id, 1, null)) AS count"),
                'ans.color',
                'q.chart_type as chartType'
            );

        $data = $subQuery->get();

        $groupedData = $data->groupBy(['experience', 'label'])
            ->each(function ($experiences) {

                // estructurar la data para pasarla al chart, convirtiendo los registros en columnas
                foreach ($experiences as $experience) {
                    $experience->valueColumns = [];
                    $experience->charts = new \stdClass();

                    $experience->transformedData = new \stdClass();
                    $columns = [];

                    foreach ($experience as $question) {
                        if (!empty($columns[$question->label]) && in_array($question->answer, $columns[$question->label])) {
                            continue;
                        }
                        $columns[$question->label][] = $question->answer;
                        $experience->valueColumns[] = [
                            'label' => $question->answer,
                            'field' => $question->answer,
                            'color' => $question->color,
                            'value' => $question->count
                        ];


                        $column = $question->answer;

                        $temp = $experience->transformedData;
                        $temp->label = $question->label;
                        $temp->$column = $question->count;
                        $experience->transformedData = $temp;
                        $experience->chartType = $question->chartType;
                    }

                    if ($experience->chartType == 'line') {
                        $config = [
                            "labelColumn" => 'label',
                            "valueColumns" => $experience->valueColumns
                        ];

                        $experience->charts->line = $this->chart->getChartLine([$experience->transformedData], $config);
                    } else if ($experience->chartType == 'pie') {
                        $chart = [
                            [
                                "label" => "SI",
                                "value" => $experience->transformedData->SI,
                                'color' => '#22b14c'
                            ],
                            [
                                "label" => "NO",
                                "value" => $experience->transformedData->NO,
                                'color' => '#ff0000'
                            ]
                        ];

                        $experience->charts->label = $experience->transformedData->label;

                        $temp = new \stdClass();
                        $temp->label = $experience->transformedData->label;
                        $temp->pie =  $this->chart->getChartPie(json_decode(json_encode($chart)));
                        $experience->charts->pie = $temp;
                    }
                }
            });

        $result = [];
        foreach ($groupedData as $index => $groupedDatum) {
            $temp = new \stdClass();
            $temp->experience = $index;
            $temp->charts = new \stdClass();

            foreach ($groupedDatum as $question) {
                if (!empty($question->charts->line)) {
                    $temp->charts->line[] = $question->charts->line;
                }

                if (!empty($question->charts->pie)) {
                    $temp->charts->pie[] = $question->charts->pie;
                }
            }

            $result[] = $temp;
        }

        return $result;
    }


    public function getChartLineRegisteredVsParticipantsByMonths($startDate, $endDate, $customerId)
    {

        $subquery = self::getQueryRegisterVsParticipants($startDate, $endDate, $customerId);

        $data = $subquery->get();

        $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.dynamicColum')
            ->orderBy('d.year')
            ->orderBy('d.month')
            ->select('d.dynamicColum as label');


        $labels = [];
        $valueColumns = [];

        foreach ($data as $datum) {
            if (in_array($datum->label, $labels)) {
                continue;
            }

            $labels[] = $datum->label;
            $valueColumns[] = ['label' => $datum->label, 'field' => $datum->label];

            $query->addSelect(
                DB::raw("SUM(CASE WHEN label = '{$datum->label}' THEN total ELSE 0 END) AS '{$datum->label}'")
            );
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartPieRegisteredVsParticipantsAllClientsAndPeriods($startDate, $endDate, $customerId)
    {

        $registered = DB::table('wg_customer_vr_employee_consolidate as c')
            ->where('c.date', '>=', $startDate)
            ->where('c.date', '<=', $endDate)
            ->when($customerId, function($query) use ($customerId) {
                return $query->where('c.customer_id', $customerId);
            })
            ->select(
                DB::raw("'Registrados' AS label"),
                DB::raw("count(total) AS total")
            )
            ->first();

        $participants = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->where('r.date_register', '>=', $startDate)
            ->where('r.date_register', '<=', $endDate)
            ->when($customerId, function($query) use ($customerId) {
                return $query->where('r.customer_id', $customerId);
            })
            ->select(
                DB::raw("'Participantes' AS label"),
                DB::raw("count(DISTINCT r.`group`) AS total")
            )
            ->first();


        $total = $registered ? $registered->total : 0;
        $totalParticipants = $participants ? $participants->total : 0;
        $remaining = $total - $totalParticipants;
        $factor = ($total == 0) ? 1 : $total;
        $percent = ($totalParticipants * 100) / $factor;
        $percent_remaining = 100 - $percent;

        $chart = [
            [
                "label" => "$totalParticipants SI",
                "value" => round($percent, 2)
            ],
            [
                "label" => "{$remaining} NO",
                "value" => round($percent_remaining, 2)
            ]
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }


    public static function getQueryRegisterVsParticipants($startDate, $endDate, $customerId)
    {

        $participants = DB::table('wg_customer_vr_employee_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'm')), function ($join) {
                $join->whereRaw("MONTH(date) = m.value");
            })
            ->whereDate('c.date', '>=', $startDate)
            ->whereDate('c.date', '<=', $endDate)
            ->when($customerId, function($query) use ($customerId) {
                return $query->where('c.customer_id', $customerId);
            })
            ->groupBy(
                DB::raw("year(date)"),
                DB::raw("month(date)")
            )
            ->orderBy('date')
            ->select(
                DB::raw("'Participantes' AS label"),
                DB::raw("CONCAT(YEAR(date), '-', m.item) AS dynamicColum"),
                DB::raw("sum(total) AS total"),
                DB::raw('YEAR(date) AS year'),
                DB::raw('MONTH(date) AS month')
            );


        $encuestados = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'm')), function ($join) {
                $join->whereRaw("MONTH(r.date_register) = m.value");
            })
            ->whereDate('r.date_register', '>=', $startDate)
            ->whereDate('r.date_register', '<=', $endDate)
            ->when($customerId, function($query) use ($customerId) {
                return $query->where('r.customer_id', $customerId);
            })
            ->groupBy(
                DB::raw("year(r.date_register)"),
                DB::raw("month(r.date_register)")
            )
            ->select(
                DB::raw("'Encuestados' AS label"),
                DB::raw("CONCAT(YEAR(r.date_register), '-', m.item) AS dynamicColum"),
                DB::raw("count(DISTINCT r.`group`) AS total"),
                DB::raw('YEAR(r.date_register) AS year'),
                DB::raw('MONTH(r.date_register) AS month')
            );

        return $participants
            ->unionAll($encuestados)->mergeBindings($encuestados);
    }
}
