'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerEmployeeEmployeesCtrl', function ($scope, $rootScope, ChartService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.chart = {
            bar: {options: null},
            data: {
                countEmployees: 0,
                countEmployeesEconomicGroup: 0,
                countEmployeesContrators: 0,
                total: 0,
                amountEmployeesChartStackedBar: null
            }
        };
    };

    load();

    $scope.$on('onBroadcastChangeFilterCustomer', function () {
        load();
    });

    function load() {
        $scope.init();
        $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

        if ($scope.currentCustomer) {
            getCharts();
        }
    }


    function getCharts() {
        var $criteria = {customerId: $scope.currentCustomer.id};

        var entities = [
            {name: 'chart_bar_with_scales_options', criteria: null},
            {name: 'dashboard_customer_employees_amounts', criteria: $criteria}
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptionsWithScales;
                $scope.chart.bar.options.legend.display = false;

                $scope.chart.data.countEmployees = response.data.data.dashboardCustomerEmployeesAmounts.countEmployees;
                $scope.chart.data.countEmployeesEconomicGroup = response.data.data.dashboardCustomerEmployeesAmounts.countEmployeesEconomicGroup;
                $scope.chart.data.countEmployeesContrators = response.data.data.dashboardCustomerEmployeesAmounts.countEmployeesContrators;
                $scope.chart.data.total = response.data.data.dashboardCustomerEmployeesAmounts.total;

                $scope.chart.data.amountEmployeesChartStackedBar = response.data.data.dashboardCustomerEmployeesAmountsChartStackedBar;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
