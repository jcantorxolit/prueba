<?php

namespace AdeN\Api\Modules\Customer\Employee\Indicators;

use Illuminate\Support\Facades\DB;
use AdeN\Api\Classes\BaseRepository;

class CustomerEmployeeIndicatorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerEmployeeIndicatorModel());
        $this->service = new CustomerEmployeeDemographicIndicatorService();
    }


    public function setCustomerId(int $customerId) {
        $this->service->setCustomerId($customerId);
    }

    public function setWorkplace($workplace) {
        $this->service->setWorkplace($workplace);
    }


    public function consolidateStatusEmployees(int $customerId) {
        $this->service->consolidateStatusEmployees($customerId);
    }


    public function consolidateSupportDocuments(int $customerId) {
        $this->service->consolidateSupportDocuments($customerId);
    }


    public function consolidateDemographic(int $customerId) {
        $this->service->consolidateDemographic($customerId);
    }



    public function getTypeHousingChartPie() {
        return $this->service->getTypeHousingChartPie();
    }

    public function getAntiquityCompanyChartPie() {
        return $this->service->getAntiquityCompanyChartPie();
    }

    public function getAntiquityJobChartPie() {
        return $this->service->getAntiquityJobChartPie();
    }

    public function getHasChildrenChartPie() {
        return $this->service->getHasChildrenChartPie();
    }

    public function getGenderChartPie() {
        return $this->service->getGenderChartPie('gender');
    }

    public function getCharBarStratum() {
        return $this->service->getCharBarStratum();
    }

    public function getCharBarCivilStatus() {
        return $this->service->getCharBarCivilStatus();
    }

    public function getCharBarScholarship() {
        return $this->service->getCharBarScholarship();
    }

    public function getCharBarAge() {
        return $this->service->getCharBarAge();
    }

    public function getCharBarPracticeSports() {
        return $this->service->getCharBarPracticeSports();
    }

    public function getCharBarDrinkAlcoholic() {
        return $this->service->getCharBarDrinkAlcoholic();
    }

    public function getCharBarSmokes() {
        return $this->service->getCharBarSmokes();
    }


    public function getCharBarDiagnosedDisease() {
        return $this->service->getCharBarDiagnosedDisease();
    }


    public function getCharBarWorkArea() {
        return $this->service->getCharBarWorkArea();
    }

    public function getCharBarWorkShift() {
        return $this->service->getCharBarWorkShift();
    }


    public static function getYears(int $customerId) {
        return DB::table('wg_customer_employee_status_consolidate')
            ->where('customer_id', $customerId)
            ->groupBy(DB::raw('year(period)'))
            ->orderBy('period', 'desc')
            ->select(DB::raw('year(period) as year'))
            ->get()
            ->toArray();
    }


}
