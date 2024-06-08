'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerEmployeeAbsenteeismCtrl', function ($scope, $rootScope, $filter, ChartService, ListService, DashboardFilterService) {

    var firstLoad = true;

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.causes = [];
        $scope.periods = [];

        $scope.entity = {
            cause: null,
            period: null,
            comparePeriod: null,
        }

        $scope.chart = {
            line: { options: null },
            data: {
                customerAbsenteeismDisabilityGeneralEvent: null,
            }
        };
    };

    load();

    $scope.$on('onBroadcastChangeFilterCustomer', function () {
        load();
    });

    $scope.onRefresh = function () {
        getPeriodList();
    };

    $scope.onRefreshChart = function () {
        getCharts();
    };

    $scope.onClearComparePeriod = function () {
        $scope.entity.comparePeriod = null;
        $scope.onRefresh();
    };


    function load() {
        $scope.init();
        $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

        if ($scope.currentCustomer) {
            getList();
        }
    }


    function getList() {
        var $criteria = { customerId: $scope.currentCustomer.id };

        var entities = [
            { name: 'dashboard_absenteeism_disability_causes_only', criteria: $criteria },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.causes = response.data.data.absenteeism_disability_causes_only;

                var existsAL = $scope.causes.find(function (x) {
                    return x.value == "AL";
                })

                if (existsAL) {
                    $scope.entity.cause = existsAL;
                    getPeriodList();
                }

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function getPeriodList() {
        var $criteria = { customerId: $scope.currentCustomer.id, cause: $scope.entity.cause ? $scope.entity.cause.value : null };

        var entities = [
            { name: 'current_year' },
            { name: 'dashboard_absenteeism_disability_indicator_years', criteria: $criteria },
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periods = response.data.data.absenteeism_disability_indicator_years;
                var previousPeriod = $scope.entity.period;
                var previousComparePeriod = $scope.entity.comparePeriod;
                $scope.entity.period = null;
                $scope.entity.comparePeriod = null;
                $scope.chart.data.customerAbsenteeismDisabilityGeneralEvent = null;

                var $currentYear = response.data.data.currentYear;
                var $firstPeriod = $scope.periods.length ? $scope.periods[0] : null;
                var $result = $filter('filter')($scope.periods, { value: $currentYear });
                $scope.entity.period = $result.length ? $result[0] : $firstPeriod;

                getCharts();
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getCharts() {
        if (!$scope.entity.cause) {
            return;
        }

        var periods = [
            $scope.entity.period ? $scope.entity.period.value : -1
        ];

        if ($scope.entity.comparePeriod) {
            periods.push($scope.entity.comparePeriod.value);
        }

        var $criteria = {
            customerId: $scope.currentCustomer.id,
            cause: $scope.entity.cause,
            yearList: periods
        };

        var entities = [
            { name: 'chart_line_options', criteria: null },
            { name: 'customer_absenteeism_disability_general_event', criteria: $criteria }
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.line.options = response.data.data.chartLineOptions;
                $scope.chart.data.customerAbsenteeismDisabilityGeneralEvent = response.data.data.customerAbsenteeismDisabilityGeneralEvent;
            }, function (error) {

            });
    }

});
