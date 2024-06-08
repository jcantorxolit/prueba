<?php

namespace AdeN\Api\Modules\Customer\Employee;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerEmployeeService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findInCustomer($criteria)
    {
        return DB::table('wg_customer_employee')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
            })
            ->join("wg_employee", function ($join) {
                $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
            })
            ->leftjoin("wg_customer_user", function ($join) {
                $join->on('wg_customer_user.documentType', '=', 'wg_employee.documentType');
                $join->on('wg_customer_user.documentNumber', '=', 'wg_employee.documentNumber');
            })
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
            })
            ->select(
                'wg_customer_employee.id',
                'wg_customer_user.user_id as userId',
                'users.email as userEmail'
            )
            ->where("wg_customer_employee.customer_id", $criteria->customerId)
            ->where("wg_employee.documentType", $criteria->employeeDocumentType)
            ->where("wg_employee.documentNumber", $criteria->employeeDocumentNumber)
            ->first();
    }

    public function findInDifferentCustomer($criteria)
    {
        return DB::table('wg_customer_employee')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
            })
            ->join("wg_employee", function ($join) {
                $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
            })
            ->leftjoin("wg_customer_user", function ($join) {
                $join->on('wg_customer_user.documentType', '=', 'wg_employee.documentType');
                $join->on('wg_customer_user.documentNumber', '=', 'wg_employee.documentNumber');
            })
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_user.user_id');
            })
            ->select(
                'wg_customer_employee.id',
                'wg_customer_user.user_id as userId',
                'users.email as userEmail'
            )
            ->where("wg_customer_employee.customer_id", '<>', $criteria->customerId)
            ->where("wg_employee.documentType", $criteria->employeeDocumentType)
            ->where("wg_employee.documentNumber", $criteria->employeeDocumentNumber)
            ->first();
    }

    public function findByDocument($criteria)
    {
        return DB::table('wg_customer_employee')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customers.id');
            })
            ->join("wg_employee", function ($join) {
                $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
            })
            ->select(
                'wg_customer_employee.id'
            )
            ->where("wg_employee.documentType", $criteria->employeeDocumentType)
            ->where("wg_employee.documentNumber", $criteria->employeeDocumentNumber)
            ->first();
    }
}
