'use strict';
/**
 * controller for Customers Employees
 */
app.controller('customerEmployeeIndicatorsEmployeesCtrl', function ($scope, $stateParams, ListService, ChartService, $http, SweetAlert, $log, $timeout) {

    $scope.workplaces = [];

    $scope.filters = {
        workplace: null
    }

    $scope.chart = {
        bar: {options: null},
        horizontalBar: {options: null},
        doughnut: {options: null},
        data: {
            typeHousing: null,
            antiquityCompany: null,
            antiquityJob: null,

            hasChildren: null,
            stratum: null,
            civilStatus: null,

            gender: null,
            scholarship: null,
            age: null,

            practiceSports: null,
            drinkAlcoholic: null,
            smokes: null,

            diagnosedDisease: null,
            workArea: null,
            workShift: null
        }
    };


    getLists();
    getCharts();


    $scope.onGoToSummary = function () {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("summary");
        }
    }

    $scope.onChangeWorkplace = function () {
        getCharts();
    };

    $scope.onClearFilterWorkplace = function () {
        $scope.filters.workplace = null;
        getCharts();
    };


    $scope.onConsolidate = function () {
        var req = {
            customerId: $stateParams.customerId
        }

        return $http({
            method: 'POST',
            url: 'api/customer-employee-indicators/consolidateDemographic',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function () {
            getCharts();
            SweetAlert.swal("Proceso Exitoso", ".", "success");
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error al consolidar", e.data.message, "error");
        });
    };



    function getLists() {
        var req = {customerId: $stateParams.customerId}

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.workplaces = response.data.data;
        });
    }


    function getCharts() {
        var entities = [
            {name: 'chart_bar_options', criteria: null},
            {name: 'chart_doughnut_options', criteria: null},
            {name: 'customer_employee_demographic_indicators', criteria: {
                    customerId: $stateParams.customerId,
                    workplace: $scope.filters.workplace == null ? null : $scope.filters.workplace.id
                }},
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $timeout(function() {
                    $scope.chart.bar.options = angular.copy(response.data.data.chartBarOptions);
                    $scope.chart.bar.options.legend.position = 'top';

                    $scope.chart.horizontalBar.options = angular.copy(response.data.data.chartBarOptions);
                    $scope.chart.horizontalBar.options.legend.position = 'top';
                    $scope.chart.horizontalBar.options.scales.yAxes[0].ticks.display = false;
                    $scope.chart.horizontalBar.options.scales.xAxes[0].ticks.display = false;

                    $scope.chart.doughnut.options = response.data.data.chartDoughnutOptions;
                    $scope.chart.doughnut.options.legend.position = 'top';
                    $scope.chart.doughnut.options.maintainAspectRatio = false;
                    $scope.chart.doughnut.options.responsive = false;
                });


                $scope.chart.data.typeHousing = response.data.data.customerEmployeeDemographicIndicatorsTypeHousing;
                $scope.chart.data.antiquityCompany = response.data.data.customerEmployeeDemographicIndicatorsAntiquityCompany;
                $scope.chart.data.antiquityJob = response.data.data.customerEmployeeDemographicIndicatorsAntiquityJob;

                $scope.chart.data.hasChildren = response.data.data.customerEmployeeDemographicIndicatorsHasChildren;
                $scope.chart.data.stratum = response.data.data.customerEmployeeDemographicIndicatorsStratum;
                $scope.chart.data.civilStatus = response.data.data.customerEmployeeDemographicIndicatorsCivilStatus;

                $scope.chart.data.gender = response.data.data.customerEmployeeDemographicIndicatorsGender;
                $scope.chart.data.scholarship = response.data.data.customerEmployeeDemographicIndicatorsScholarship;
                $scope.chart.data.age = response.data.data.customerEmployeeDemographicIndicatorsAge;

                $scope.chart.data.practiceSports = response.data.data.customerEmployeeDemographicIndicatorsPracticeSports;
                $scope.chart.data.drinkAlcoholic = response.data.data.customerEmployeeDemographicIndicatorsDrinkAlcoholic;
                $scope.chart.data.smokes = response.data.data.customerEmployeeDemographicIndicatorsSmokes;

                $scope.chart.data.diagnosedDisease = response.data.data.customerEmployeeDemographicIndicatorsDiagnosedDisease;
                $scope.chart.data.workArea = response.data.data.customerEmployeeDemographicIndicatorsWorkArea;
                $scope.chart.data.workShift = response.data.data.customerEmployeeDemographicIndicatorsWorkShift;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
