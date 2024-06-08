<?php

namespace AdeN\Api\Modules\Customer\User;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CmsHelper;
use DB;
use Illuminate\Database\Eloquent\Collection;

class CustomerUserService extends BaseService
{
    private $lists;

    public function __construct()
    {
        parent::__construct();
    }

    public function findSignUpDefaultUserRole()
    {
        return DB::table('system_parameters')->where('namespace', 'wgroup')->where('group', 'regiter_default_user_role')->first();
    }

    public function assignRoleCustomerSize($user)
    {
        $roles = ["MC" => "MICRO", "PQ" => "PEQUEÑA", "MD" => "MEDIANA", "GD" => "GRANDE"];

        $customerSize = DB::table('wg_customers')->where('id', $user->company)->pluck('size');

        $roleName = key_exists($customerSize, $roles) ? $roles[$customerSize] : null;

        if ($roleName) {
            $role = DB::table('shahiemseymor_roles')->where('name', $roleName)->first();

            if ($role) {
                DB::table('shahiemseymor_assigned_roles')->insert(
                    ['user_id' => $user->id, 'role_id' => $role->id]
                );
            }
        }
    }

    public static function assignRole($userId, $newRoleId, $oldRoleId)
    {
        DB::table('users_groups')
            ->where('user_id', $userId)
            ->where('user_group_id', $oldRoleId)
            ->delete();

        $query = DB::table('user_groups')
            ->select(
                DB::raw('? AS user_id'),
                'user_groups.id as user_group_id'
            )
            ->addBinding($userId, "select")
            ->where('id', $newRoleId);

        $sql = 'INSERT INTO users_groups (`user_id`, `user_group_id`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function trackingCount($id)
    {
        return DB::table('wg_customer_tracking')->where('agent_id', $id)->where('userType', 'Cliente Usuario')->count();
    }

    public function improvementPlanCount($id)
    {
        return DB::table('wg_customer_improvement_plan')->where('responsible', $id)->where('responsibleType', 'Cliente Usuario')->count();
    }

    public function internalProjectCount($id)
    {
        return DB::table('wg_customer_internal_project_user')->where('agent_id', $id)->where('agent_type', 'Cliente Usuario')->count();
    }

    public function inactiveInAllCustomerRelated($userId)
    {
        DB::table('wg_customer_user')
            ->where('user_id', $userId)
            ->update([
                "isActive" => 0
            ]);
    }

    public function getCustomerList($criteria)
    {
        $q1 = DB::table('wg_customers')
            ->select(
                'wg_customers.id',
                'wg_customers.id AS value',
                'wg_customers.businessName AS item',
                DB::raw("'Empresa Actual' AS relation"),
                DB::raw("'CC' AS relationCode")
            )
            ->where('wg_customers.status', 1);

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.customer_id');
            })
            ->join(DB::raw('wg_customers AS wg_contractor'), function ($join) {
                $join->on('wg_contractor.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_contractor.id',
                'wg_contractor.id AS value',
                'wg_contractor.businessName AS item',
                DB::raw("'Empresa Contratista' AS relation"),
                DB::raw("'CT' AS relationCode")
            )
            ->where('wg_customers.classification', 'Contratante')
            ->where('wg_customers.status', 1)
            ->where('wg_contractor.status', 1)
            ->where("wg_contractor.isDeleted", 0)
            ->orderBy('wg_contractor.businessName');

        $q3 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.parent_id');
            })
            ->join(DB::raw('wg_customers AS wg_economic_group'), function ($join) {
                $join->on('wg_economic_group.id', '=', 'wg_customer_economic_group.customer_id');
            })
            ->select(
                'wg_economic_group.id',
                'wg_economic_group.id AS value',
                'wg_economic_group.businessName AS item',
                DB::raw("'Empresa del Grupo Económico' AS relation"),
                DB::raw("'EG' AS relationCode")
            )
            ->where('wg_customers.hasEconomicGroup', 1)
            ->where('wg_customers.status', 1)
            ->where('wg_economic_group.status', 1)
            ->where("wg_economic_group.isDeleted", 0)
            ->orderBy('wg_economic_group.businessName');

        if (isset($criteria->customerId) && $criteria->customerId) {
            $q1->where('wg_customers.id', $criteria->customerId);
            $q2->where('wg_customers.id', $criteria->customerId);
            $q3->where('wg_customers.id', $criteria->customerId);
        }

        $q1->union($q2)->union($q3);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customers"))
            ->mergeBindings($q1)
            ->orderBy('wg_customers.item')
            ->groupBy('wg_customers.item');

        return $query->get();
    }

    public function hasCustomerAdmin($criteria)
    {
        return DB::table('users')
            ->where('company', $criteria->customerId)
            ->where('wg_type', 'customerAdmin')
            ->count() > 0;
    }

    public function getParameterList($group)
    {
        return DB::table('system_parameters')
            ->select(
                DB::raw("TRIM(UPPER(`item`)) AS `item`"),
                "value",
                'code'
            )
            ->where('namespace', 'wgroup')
            ->where('group', $group)
            ->get()->toArray();
    }

    public function getGender($value)
    {
        if (!$this->listExists('gender')) {
            $this->lists['gender'] = new  Collection($this->getParameterList('gender'));
        }

        $param = $this->lists['gender']->filter(function($item) use ($value) {
            return $item->item == $this->parseValue($value);
        })->first();

        return $param ? $param->value : null;
    }

    public function getType($value)
    {
        if (!$this->listExists('type')) {
            $this->lists['type'] = new  Collection($this->getParameterList('agent_type'));
        }

        $param = $this->lists['type']->filter(function($item) use ($value) {
            return $item->item == $this->parseValue($value);
        })->first();

        return $param ? $param->value : null;
    }

    public function getProfile($value)
    {
        if (!$this->listExists('profile')) {
            $this->lists['profile'] =  new  Collection($this->getParameterList('wg_customer_user_profile'));
        }

        $param = $this->lists['profile']->filter(function($item) use ($value) {
            return $item->item == $this->parseValue($value);
        })->first();

        return $param ? $param->value : null;
    }

    public function getRole($value)
    {
        if (!$this->listExists('role')) {
            $this->lists['role'] = new  Collection($this->getParameterList('customer_user_role'));
        }

        $param = $this->lists['role']->filter(function($item) use ($value) {
            return $item->item == $this->parseValue($value);
        })->first();

        return $param ? $param->value : null;
    }

    private function parseValue($value)
    {
        return mb_strtoupper(trim($value));
    }

    private function listExists($key)
    {
        $this->lists = $this->lists ? $this->lists : [];
        return (key_exists($key, $this->lists));
    }

    public function getStagingValidData($sessionId, $customerId)
    {
        $data = DB::table('wg_customer_user_staging')
            ->where('session_id', $sessionId)
            ->where('customer_id', $customerId)
            ->where('is_valid', 1)
            ->get();

        return CmsHelper::parseToStdClass((new Collection($data))->map(function ($item) {
            return [
                "id" => null,
                "customerId" => $item->customer_id,
                "mainCustomer" => null,
                "documentType" => [
                    "value" => $item->document_type
                ],
                "documentNumber" => $item->document_number,
                "gender" => [
                    "value" => $item->gender
                ],
                "type" => [
                    "value" => $item->type
                ],
                "availability" => null,
                "isActive" => $item->is_active,
                "isUserApp" => $item->is_user_app,
                "role" => [
                    "value" => $item->role
                ],
                "firstName" => $item->first_name,
                "lastName" => $item->last_name,
                "email" => $item->email,
                "profile" =>  [
                    "value" => $item->profile
                ],
                "userId" => null,
                "index" => $item->index
            ];
        })->toArray());
    }

    public function getStagingInvalidData($sessionId, $customerId)
    {
        $data = DB::table('wg_customer_user_staging')
            ->where('session_id', $sessionId)
            ->where('customer_id', $customerId)
            ->where('is_valid', 0)
            ->get();

        return CmsHelper::parseToStdClass((new Collection($data))->map(function ($item) {
            return [
                $item->observation
            ];
        })->toArray());
    }
}
