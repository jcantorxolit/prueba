'use strict';
/**
 * controller for Customers Employees
 */
app.controller('customerEmployeeIndicatorsSummaryCtrl', function ($scope, $stateParams, ListService, ChartService, $http, $log, SweetAlert, $timeout) {

    $scope.yearList = [];
    $scope.workplaces = [];

    $scope.totalEmployees = 0;
    $scope.totalDocuments = 0;

    $scope.filters = {
        year: null,
        workplace: null
    }

    $scope.chart = {
        line: {options: null},
        doughnut: {options: null},
        barWithScales: {options: null},
        data: {
            activeEmployees: null,
            autorizedEmployees: null,

            employeesByWorkplaces: null,
            activeEmployeesByWorkplaces: null,
            autorizedEmployeesByWorkplaces: null,

            amountEmployeesByPeriod: null,
            amountEmployeesVsActiveVsInactiveByPeriod: null,
            amountActiveVsAutorizedVsUnautorizedEmployeesByPeriod: null,
        }
    };


    getList();
    getWorkplaces();


    $scope.onGoToEmployeeIndicators = function () {
        if ($scope.$parent != null) {
            $scope.$parent.navToSection("employeeIndicators");
        }
    };

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
            url: 'api/customer-employee-indicators/consolidateStatusEmployees',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function () {
            getList();
            SweetAlert.swal("Proceso Exitoso", ".", "success");
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error al consolidar", e.data.message, "error");
        });
    };



    function getList() {
        var entities = [{
            name: 'customer_employee_indicators_years',
            criteria: { customerId: $stateParams.customerId }
        }];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.yearList = response.data.data.customerEmployeeIndicatorsYears;
                if ($scope.yearList.length) {
                    $scope.filters.year = $scope.yearList[0];
                    getCharts();
                }

            }, function (error) {
                $scope.status = "Unable to load customer data: " + error.message;
            });
    }


    function getWorkplaces() {
        var req = {customerId: $stateParams.customerId }

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.workplaces = response.data.data;
        });
    }


    function setupCharts(data) {
        $timeout(function() {
            $scope.chart.line.options = data.chartLineOptions;
            $scope.chart.line.options.legend.position = 'top';
            $scope.chart.line.options.scales.xAxes = [{
                ticks: {
                    autoSkip: false
                }
            }];

            $scope.chart.doughnut.options = data.chartDoughnutOptions;
            $scope.chart.doughnut.options.legend.position = 'top';
            $scope.chart.doughnut.options.maintainAspectRatio = false;
            $scope.chart.doughnut.options.responsive = false;

            $scope.chart.barWithScales.options = data.chartBarOptionsWithScales;
            $scope.chart.barWithScales.options.legend.position = 'top';

            // show all columns
            var xAxes = $scope.chart.barWithScales.options.scales.xAxes;
            xAxes[0].ticks = {
                autoSkip: false
            }

            $scope.chart.barWithScales.options.scales.xAxes = xAxes;
        });
    }


    function getCharts() {
        var entities = [
            {name: 'chart_line_options', criteria: null},
            {name: 'chart_doughnut_options', criteria: null},
            {name: 'chart_bar_with_scales_options', criteria: null},
            {name: 'customer_employee_indicators_summary', criteria: {
                customerId: $stateParams.customerId,
                year: $scope.filters.year.year,
                workplace: $scope.filters.workplace == null ? null : $scope.filters.workplace.id
            }},
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                setupCharts(response.data.data);

                $scope.totalEmployees = response.data.data.customerEmployeeIndicatorsSummaryTotal;
                $scope.chart.data.activeEmployees = response.data.data.customerEmployeeIndicatorsSummaryActive;
                $scope.chart.data.autorizedEmployees = response.data.data.customerEmployeeIndicatorsSummaryAuthorized;

                $scope.chart.data.employeesByWorkplaces = response.data.data.customerEmployeeIndicatorsEmployeesByWorkplaces;
                $scope.chart.data.activeEmployeesByWorkplaces = response.data.data.customerEmployeeIndicatorsActiveEmployees;
                $scope.chart.data.autorizedEmployeesByWorkplaces = response.data.data.customerEmployeeIndicatorsAutorizedEmployees;

                $scope.chart.data.amountEmployeesByPeriod = response.data.data.customerEmployeeIndicatorsAmountEmployeesByPeriod;
                $scope.chart.data.amountEmployeesVsActiveVsInactiveByPeriod = response.data.data.customerEmployeeIndicatorsAmountEmployeesVsActiveVsInactiveByPeriod;
                $scope.chart.data.amountActiveVsAutorizedVsUnautorizedEmployeesByPeriod = response.data.data.customerEmployeeIndicatorsAmountamountActiveVsAutorizedVsUnautorizedEmployeesByPeriodByPeriod;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
