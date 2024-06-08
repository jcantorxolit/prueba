<?php

namespace Wgroup\NephosIntegrationProductPlan;

use DB;
use Exception;
use Log;
use Str;


class NephosIntegrationProductPlanService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerRepository();
    }

    public function init()
    {
        parent::init();
    }
}