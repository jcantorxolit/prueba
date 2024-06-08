'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerEmployeeVirtualRealityCtrl', function ($scope, $rootScope, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.periods = [];

        $scope.entity = {
            period: null
        }

        $scope.chart = {
            bar: {options: null},
            doughnut: {options: null},
            data: {
                genre: {},
                genreTotal: 0,
                competitorExperience: {},
            }
        };
    };

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
            name: 'customer_vr_employee_indicators_period_list', criteria: {
                customerId: $scope.currentCustomer.id
            }
        }];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periods = response.data.data.periodList;

                if ($scope.periods.length > 0) {
                    $scope.entity.period = $scope.periods[0];
                    $scope.onRefresh();
                }

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getCharts() {
        var entities = [
            {name: "chart_bar_options"},
            {
                name: "customer_vr_employee_indicators_charts",
                criteria: {
                    customerId: $scope.currentCustomer.id,
                    selectedYear: $scope.entity.period ? $scope.entity.period.value : null
                }
            },
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptions;
                $scope.chart.data.genre = response.data.data.genre;
                $scope.chart.data.genreTotal = response.data.data.genreTotal;
                $scope.chart.data.competitorExperience = response.data.data.competitorExperience;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
