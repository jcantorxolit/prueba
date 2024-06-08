<?php

namespace AdeN\Api\Modules\Customer\Parameter;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Wgroup\CustomerParameter\CustomerParameter;


class CustomerParameterService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getOfficeTypeMatrixSpecialCount($id)
    {
        return DB::table('wg_customer_config_office_special')
            ->where('type', $id)
            ->count();
    }

    public function getBusinessUnitMatrixSpecialCount($id)
    {
        return DB::table('wg_customer_config_office_special')
            ->join('wg_customer_config_business_unit_special_relation', function ($join) {
                $join->on('wg_customer_config_office_special.id', '=', 'wg_customer_config_business_unit_special_relation.customer_office_special_id');
                $join->on('wg_customer_config_office_special.customer_id', '=', 'wg_customer_config_business_unit_special_relation.customer_id');
            })
            ->where('wg_customer_config_business_unit_special_relation.customer_business_unit_special_id', $id)
            ->count();
    }


    public static function getCustomerParameter($customerId, $group, $alone = false, $value = null, $ns = 'wgroup')
    {
        $query = CustomerParameter::whereCustomerId($customerId)
            ->whereGroup($group)
            ->whereNamespace($ns);

        if (!empty($value)) {
            return $query->whereValue($value)->first();
        }

        if ($alone) {
            return $query->first();
        }

        return $query->get();
    }

}
