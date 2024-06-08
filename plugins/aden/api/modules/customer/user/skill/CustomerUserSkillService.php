<?php

namespace AdeN\Api\Modules\Customer\User\Skill;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerUserSkillService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getSkills($customerUserId)
    {
        $query = DB::table('wg_customer_user_skill')
            ->join("wg_customer_user", function ($join) {
                $join->on("wg_customer_user.id", "=", "wg_customer_user_skill.customer_user_id");
            })
            ->join("wg_customer_parameter", function ($join) {
                $join->on("wg_customer_parameter.id", "=", "wg_customer_user_skill.skill");
                $join->where("wg_customer_parameter.group", "=", "userSkill");
            })
            ->select(
                'wg_customer_user_skill.id',
                'wg_customer_user_skill.customer_user_id',
                'wg_customer_parameter.id AS skill_id',
                'wg_customer_parameter.item AS skill_is_active',
                'wg_customer_parameter.value AS skill_value',
                'wg_customer_parameter.data AS skill_data'
            )
            ->where('wg_customer_user_skill.customer_user_id', $customerUserId);

        return array_map(function ($item) {
            return [
                'id' => $item->id,
                'customerUserId' => $item->customer_user_id,
                'skill' => [
                    'id' => $item->skill_id,
                    'isActive' => $item->skill_is_active == 1,
                    'value' => $item->skill_value,
                    'data' => $item->skill_data,
                ]
            ];
        }, $query->get()->toArray());
    }
}
