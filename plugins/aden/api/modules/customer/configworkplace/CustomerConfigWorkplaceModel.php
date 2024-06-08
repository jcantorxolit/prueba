<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use Illuminate\Database\Eloquent\Collection;

class CustomerConfigWorkplaceModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_config_workplace";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'city' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id']
    ];

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "wg_structure_type");
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    public function getEconomicActivity()
    {
        return DB::table('wg_investigation_economic_activity')
            ->join('wg_economic_sector', function ($join) {
                $join->on('wg_economic_sector.id', '=', 'wg_investigation_economic_activity.economic_sector_id');
            })
            ->select(
                'wg_investigation_economic_activity.id',
                'wg_investigation_economic_activity.name',
                'wg_investigation_economic_activity.code',
                'wg_economic_sector.name as economicSector'
            )
            ->where('wg_investigation_economic_activity.id', $this->economicActivityId)
            ->first();
    }

    public function getProcessList()
    {
        return DB::table('wg_customer_config_process_express')
            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_process_express_id', '=', 'wg_customer_config_process_express.id');
            })
            ->select(
                'wg_customer_config_process_express_relation.id',
                'wg_customer_config_process_express.customer_id AS customerId',
                'wg_customer_config_process_express.name',
                'wg_customer_config_process_express_relation.is_fully_configured AS isFullyConfigured'
            )
            ->where('wg_customer_config_process_express_relation.customer_workplace_id', $this->id)
            ->get();
    }

    public function getAvailableActivityList()
    {
        $q1 = DB::table('wg_customer_config_activity_express')
            ->select(
                'id',
                'name'
            )
            ->where('status', 1)
            ->where('customer_id', $this->customerId);

        $q2 = DB::table('wg_customer_config_workplace')
            ->join('wg_investigation_economic_activity', function ($join) {
                $join->on('wg_investigation_economic_activity.id', '=', 'wg_customer_config_workplace.economic_activity_id');
            })
            ->join('wg_economic_sector', function ($join) {
                $join->on('wg_economic_sector.id', '=', 'wg_investigation_economic_activity.economic_sector_id');
            })
            ->join('wg_economic_sector_task', function ($join) {
                $join->on('wg_economic_sector_task.economic_sector_id', '=', 'wg_economic_sector.id');
            })
            ->select(
                'wg_economic_sector_task.id',
                'wg_economic_sector_task.name'
            )
            ->where('wg_economic_sector_task.is_active', 1)
            ->where('wg_customer_config_workplace.customer_id', $this->customerId)
            ->where('wg_customer_config_workplace.id', $this->id);

        $q1->union($q2);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customer_config_activity_express"))
            ->mergeBindings($q1)
            ->orderBy('wg_customer_config_activity_express.name');

        return $query->get();
    }

    public function getShiftConditionList()
    {
        $qDetail = DB::table('wg_customer_config_workplace_shift_condition')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_condition.customer_workplace_id');
                $join->where('wg_customer_config_workplace.id', '=', $this->id);
            })
            ->select(
                'wg_customer_config_workplace_shift_condition.id',
                'wg_customer_config_workplace_shift_condition.customer_workplace_id',
                'wg_customer_config_workplace_shift_condition.covid_bolivar_question_code',
                'wg_customer_config_workplace_shift_condition.is_active'
            )
            ->where('wg_customer_config_workplace.id', $this->id);

        $query = DB::table('wg_covid_bolivar_question')
            ->leftjoin(DB::raw("({$qDetail->toSql()}) AS wg_customer_config_workplace_shift_condition"), function ($join) {
                $join->on('wg_customer_config_workplace_shift_condition.covid_bolivar_question_code', '=', 'wg_covid_bolivar_question.code');
            })
            ->mergeBindings($qDetail)
            ->select(
                'wg_customer_config_workplace_shift_condition.id',
                'wg_covid_bolivar_question.name',
                'wg_covid_bolivar_question.code',
                'wg_covid_bolivar_question.score',
                'wg_covid_bolivar_question.is_master',
                'wg_customer_config_workplace_shift_condition.is_active'
            )
            ->where('wg_covid_bolivar_question.is_workplace_shift_condition', 1)
            ->orderBy('wg_covid_bolivar_question.shift_sort');

        return (new Collection($query->get()))->map(function ($item) {

            switch ($item->code) {
                case 'P001':
                    $item->name = "ESTADO DE EMBARAZO";
                    break;

                case 'F003':
                    $item->name = "CONVIVENCIA CON MAYORES DE 60 AÑOS";
                    break;

                case 'F005':
                    $item->name = "CONVIVENCIA CON PERSONAL DE LA SALUD";
                    break;
            }

            return [
                'id' => $item->id ? $item->id : 0,
                'name' => $item->name,
                'covidBolivarQuestionCode' => $item->code,
                'score' => $item->score,
                'isMaster' => $item->is_master == 1,
                'isActive' => $item->is_active == 1
            ];
        });
    }

    public function getTotalEmployee()
    {
        return  DB::table('wg_customer_covid_bolivar')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->where('wg_customer_covid_bolivar.customer_workplace_id', $this->id)
            ->where('wg_customer_config_workplace.id', $this->id)
            ->count();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
