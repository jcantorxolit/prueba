<?php

namespace AdeN\Api\Modules\Covid\Question;

use AdeN\Api\Classes\BaseService;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Str;


class CovidQuestionService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList()
    {
        return DB::table('wg_covid_question')
            ->leftjoin(DB::raw("wg_config_general AS risk_level"), function ($join) {
                $join->on('risk_level.value', '=', 'wg_covid_question.risk_level_code');
                $join->where('risk_level.type', '=', 'RISK_LEVEL_COVID_19');
            })
            ->select(
                'wg_covid_question.id',
                'wg_covid_question.name',
                'wg_covid_question.code',
                'wg_covid_question.sort',
                'risk_level.name AS riskLevelText',
                'risk_level.value AS riskLevelValue',
                'risk_level.code AS riskLevelColor',
                'risk_level.format AS riskLevelPriority'
            )
            ->orderBy('wg_covid_question.sort')
            ->get();
    }

    public function getGroupList()
    {
        $collection = DB::table('wg_covid_question_group')
            ->join(DB::raw("wg_config_general AS risk_level"), function ($join) {
                $join->on('risk_level.value', '=', 'wg_covid_question_group.risk_level_code');
                $join->where('risk_level.type', '=', 'RISK_LEVEL_COVID_19');
            })
            ->select(
                'wg_covid_question_group.id',
                'wg_covid_question_group.question_code',
                'wg_covid_question_group.group_name',
                'risk_level.name AS riskLevelText',
                'risk_level.value AS riskLevelValue',
                'risk_level.code AS riskLevelColor',
                'risk_level.format AS riskLevelPriority'
            )
            ->get();

        $groupName = $collection->groupBy('group_name');

        return $groupName->map(function ($items, $key) {
            $groupNameItems = new Collection($items);
            $item = $groupNameItems->first();
            $group = new \stdClass();
            $group->riskLevelText = $item->riskLevelText;
            $group->riskLevelValue = $item->riskLevelValue;
            $group->riskLevelColor = $item->riskLevelColor;
            $group->riskLevelPriority = $item->riskLevelPriority;

            $group->questionList = $groupNameItems->filter(function ($item) {
                return $item->question_code != null;
            })->map(function ($item, $key) {
                $question = new \stdClass();
                $question->code = $item->question_code;
                return $question;
            });

            return $group;
        })->values();
    }

    public function getRiskLevelList()
    {
        return DB::table('wg_config_general')            
            ->select(                
                'wg_config_general.name',
                'wg_config_general.value'              
            )
            ->where('type', 'RISK_LEVEL_COVID_19')
            ->orderBy('wg_config_general.name')
            ->get();
    }
}
