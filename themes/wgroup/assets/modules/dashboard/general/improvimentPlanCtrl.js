'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerImprovimentPlanCtrl', function ($scope, $rootScope, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.periods = [];

        $scope.entity = {
            period: null
        }

        $scope.chart = {
            bar: {options: null},
            data: {
                dashboardImprovementPlan: {},
            }
        };
    }

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
            name: 'dashboard_improvement_plan', criteria: {
                customerId: $scope.currentCustomer.id
            }
        }];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periods = response.data.data.dashboardImprovementPlanPeriods;

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
        };

        var entities = [
            {name: 'chart_bar_with_scales_options', criteria: null},
            {name: 'dashboard_improvement_plan', criteria: $criteria}
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptionsWithScales;
                $scope.chart.bar.options.scales.xAxes[0].ticks.autoSkip = false;
                $scope.chart.data.dashboardImprovementPlan = response.data.data.dashboardImprovementPlan;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
