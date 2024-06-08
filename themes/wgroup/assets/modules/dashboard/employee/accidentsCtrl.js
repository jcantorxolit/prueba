'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerEmployeeAccidentCtrl', function ($scope, $rootScope, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {

        $scope.periods = [];

        $scope.data = {
            totalInvestigationAt: 0,
            workingDay: [],
            amountByDayOfWeek: [],
            totalCasesWithOrigen: 0,
            totalMissedGrade: 0,
            totalCasesWithRestrictions: 0,
            chartBarBody: {},
            chartBarFactor: {},
            gender: {
                male: 0,
                female: 0
            }
        };

        $scope.entity = {
            period: null
        }

        $scope.chart = {
            bar: {options: null},
            data: {
                deathCause: null,
                accidentAgents: null,
                partesdelcuerpoafectadas: null,
            }
        };

        $scope.days = angular.copy($scope.allDays);
    }

    $scope.allDays = [
        {day: 'Do', count: 0, index: 1},
        {day: 'Lu', count: 0, index: 2},
        {day: 'Ma', count: 0, index: 3},
        {day: 'Mi', count: 0, index: 4},
        {day: 'Ju', count: 0, index: 5},
        {day: 'Vi', count: 0, index: 6},
        {day: 'Sa', count: 0, index: 7}
    ];

    load();

    $scope.$on('onBroadcastChangeFilterCustomer', function () {
        load();
    });

    $scope.onRefresh = function () {
        getCharts();
    };

    function load() {
        $scope.init();
        $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

        if ($scope.currentCustomer) {
            getList();
        }
    }


    function getList() {
        var entities = [{
            name: 'dashboard_occupational_investigation_periods', criteria: {
                customerId: $scope.currentCustomer.id
            }
        }];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periods = response.data.data.dashboardOccupationalInvestigationPeriods;

                if ($scope.periods.length > 0) {
                    $scope.entity.period = $scope.periods[0];
                    $scope.onRefresh();
                }

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getCharts() {
        var $criteria = {
            customerId: $scope.currentCustomer.id,
            period: $scope.entity.period ? $scope.entity.period.value : null,
            year: $scope.entity.period ? $scope.entity.period.value : null,
        };

        var entities = [
            {name: 'chart_bar_options', criteria: null},
            {name: 'dashboard_accidents', criteria: $criteria}
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptions;
                $scope.chart.bar.options.scales.xAxes[0].ticks.autoSkip = false;
                $scope.data = response.data.data.dashboardAccidents;

                $scope.chart.data.chartBarBody = response.data.data.chartBarBody;
                $scope.chart.data.chartBarFactor = response.data.data.chartBarFactor;

                $scope.days = angular.copy($scope.allDays);

                $scope.days.forEach(function (day, index) {
                    $scope.data.amountByDayOfWeek.forEach(function (item) {
                        if (day.index == item.day) {
                            day.count = item.count;
                        }
                    });
                });

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
