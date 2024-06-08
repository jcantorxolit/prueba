<?php

namespace Wgroup\Classes;

use Wgroup\Controllers\CustomerDiagnosticProcess;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticDTO;
use Wgroup\Models\CustomerDiagnosticProcessReporistory;
use Wgroup\Models\CustomerDiagnosticReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use DB;
use Wgroup\Models\CustomerDiagnosticRiskFactor;
use Wgroup\Models\CustomerDiagnosticWorkPlace;
use Wgroup\Models\CustomerDiagnosticWorkPlaceDTO;
use Wgroup\Models\CustomerDiagnosticWorkPlaceReporistory;

class ServiceBellGrill {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticWorkPlaceRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $diagnosticId) {
        return null;
    }

    public function getCount($search = "") {
       return null;
    }
}