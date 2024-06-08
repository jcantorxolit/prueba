<?php

namespace AdeN\Api\Modules\EmployeeInformationDetail;

use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use DB;
use Illuminate\Support\Facades\Input;
use Log;
use Str;


class EmployeeInformationDetailService
{
    function __construct()
    {

    }

    public static function save($entityName, $entityId, $type, $value, $customerId)
    {
        $info = EmployeeInformationDetailModel::where('entityName', $entityName)
            ->where('entityId', $entityId)
            ->whereType($type)
            ->first();

        if (empty($info)) {
            $info = new EmployeeInformationDetailModel();
            $info->entityName = $entityName;
            $info->entityId   = $entityId;
            $info->type       = $type;
        }

        $info->value = $value;
        $info->save();

        $customerEmployee = CustomerEmployeeModel::whereEmployeeId($entityId)
                                ->whereCustomerId($customerId)
                                ->first();

        if($customerEmployee && !$customerEmployee->primary_email) {
            $customerEmployee->primary_email = $info->id;
            $customerEmployee->save();
        }

    }
}
