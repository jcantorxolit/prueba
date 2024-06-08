<?php

namespace AdeN\Api\Modules\Customer\InternalCertificateGrade;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

class CustomerInternalCertificateGradeService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getProgramList($criteria)
    {
        return DB::table('wg_customer_internal_certificate_program')
            ->select(
                'id',
                'name'
            )
            ->where("customer_id", $criteria->customerId)
            ->where("isActive", 1)
            ->orderBy("name")
            ->get();
    }

    public function getParticipantQuery()
    {
        return DB::table('wg_customer_internal_certificate_grade_participant')
            ->select(
                'customer_internal_certificate_grade_id',
                DB::raw("COUNT(*) AS qty")
            )
            ->groupBy("customer_internal_certificate_grade_id");
    }

    public function getAgentQuery($criteria)
    {
        $query = DB::table('wg_customer_internal_certificate_grade_agent')
            ->select(
                'customer_internal_certificate_grade_id'
            )
            ->groupBy("customer_internal_certificate_grade_id");

        if ($agentId = CriteriaHelper::getMandatoryFilter($criteria, 'agentId')) {
            $query->where(SqlHelper::getPreparedField("wg_customer_internal_certificate_grade_agent.agent_id"), SqlHelper::getOperator($agentId->operator), SqlHelper::getPreparedData($agentId), 'and');
        } else {
            return null;
        }

        return $query;
    }

    public function getCalendarQuery($criteria)
    {
        if ($criteria == null) {
            return null;
        }

        $query = DB::table('wg_customer_internal_certificate_grade_calendar')
            ->select(
                'customer_internal_certificate_grade_id'
            )
            ->groupBy("customer_internal_certificate_grade_id");

        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate');
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate');
        if ($startDate && $endDate) {
            $query->whereBetween(SqlHelper::getPreparedField("wg_customer_internal_certificate_grade_calendar.startDate"), [SqlHelper::getPreparedData($startDate), SqlHelper::getPreparedData($endDate)], 'and');
        } else if ($startDate) {
            $query->where(SqlHelper::getPreparedField("wg_customer_internal_certificate_grade_calendar.startDate"), SqlHelper::getOperator($startDate->operator), SqlHelper::getPreparedData($startDate), 'and');
        } else if ($endDate) {
            $query->where(SqlHelper::getPreparedField("wg_customer_internal_certificate_grade_calendar.startDate"), SqlHelper::getOperator($startDate->operator), SqlHelper::getPreparedData($endDate), 'and');
        } else {
            return null;
        }

        return $query;
    }
}
