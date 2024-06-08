<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Covid\Daily;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerCovidDailyModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_covid";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [];


    public function  getRiskLevel()
    {
        return DB::table('wg_config_general')
            ->select(
                'wg_config_general.name AS riskLevelText',
                'wg_config_general.value AS riskLevelValue',
                'wg_config_general.code AS riskLevelColor',
                'wg_config_general.format AS riskLevelPriority'
            )
            ->where('type', 'RISK_LEVEL_COVID_19')
            ->where('value', $this->riskLevel)
            ->first();
    }

    public function getQuestionList()
    {
        return array_map(
            function ($item) {
                $item->isActive = $item->isActive == 1;
                $item->name = "{$item->sort} {$item->name}";
                return $item;
            },
            DB::table('wg_customer_covid')
                ->join("wg_customer_covid_question", function ($join) {
                    $join->on('wg_customer_covid_question.customer_covid_id', '=', 'wg_customer_covid.id');
                })
                ->join("wg_covid_question", function ($join) {
                    $join->on('wg_covid_question.code', '=', 'wg_customer_covid_question.covid_question_code');
                })
                ->leftjoin(DB::raw("wg_config_general AS risk_level"), function ($join) {
                    $join->on('risk_level.value', '=', 'wg_covid_question.risk_level_code');
                    $join->where('risk_level.type', '=', 'RISK_LEVEL_COVID_19');
                })
                ->select(
                    'wg_customer_covid_question.id',
                    'wg_customer_covid_question.customer_covid_id AS customerCovidId',
                    'wg_customer_covid_question.covid_question_code AS covidQuestionCode',
                    'wg_covid_question.name',
                    'wg_covid_question.sort',
                    'wg_customer_covid_question.is_active AS isActive',
                    'wg_customer_covid_question.observation',
                    'risk_level.name AS riskLevelText',
                    'risk_level.value AS riskLevelValue',
                    'risk_level.code AS riskLevelColor',
                    'risk_level.format AS riskLevelPriority'
                )
                ->where('wg_customer_covid_question.customer_covid_id', $this->id)
                ->orderBy('wg_covid_question.sort')
                ->get()
                ->toArray()
        );
    }

}
