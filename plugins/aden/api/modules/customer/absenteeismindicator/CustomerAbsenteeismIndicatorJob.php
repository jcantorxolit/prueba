<?php

namespace AdeN\Api\Modules\Customer\AbsenteeismIndicator;

use AdeN\Api\Helpers\EmailHelper;
use AdeN\Api\Modules\Customer\AbsenteeismIndicator\CustomerAbsenteeismIndicatorService;
use DB;


class CustomerAbsenteeismIndicatorJob
{
    protected $service;

    public function __construct()
    {
        $this->service = new CustomerAbsenteeismIndicatorService();
    }

    public function fire($job, $data)
    {
        \Log::info("Iniciar fire job");

        $criteria = json_decode (json_encode ( $data["criteria"]), FALSE);        

        $this->service->consolidate($criteria->id, $criteria->resolution, $criteria->userId);

        $this->sendNotification($criteria);

        $job->delete();

        \Log::info("Finalizar fire job");
    }

    public function sendNotification($criteria)
    {
        $criteria->module = "ANÁLISIS DE INDICADORES 0312";
        $criteria->customer = DB::table("wg_customers")->find($criteria->id)->businessName;
        EmailHelper::notifyConsolidationCompleted($criteria);
    }
}
