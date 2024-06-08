'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerEmployeeOccupationalMedicineCtrl', function ($scope, $rootScope, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.periods = [];
        $scope.workplaceList = [];

        $scope.entity = {
            period: null,
            workplace: null
        }

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            data: {
                ausentismVsInvestigationAT: {},
                chartPieAbsenteeisByCause: {},
                kpis: {
                    eventNumberAT: 0,
                    eventNumberEL: 0,
                    eventNumberEC: 0,
                    disabilityDaysAT: 0,
                    disabilityDaysEL: 0,
                    disabilityDaysEC: 0,
                    disabilityDaysTotal: 0,
                    eventNumberTotal: 0,
                }
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

    $scope.onSelectPeriod = function () {
        getWorkplaceList();
    }

    $scope.onClearWorkplace = function() {
        $scope.entity.workplace = null;
        $scope.onRefresh();
    }

    function load() {
        $scope.init();
        $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

        if ($scope.currentCustomer) {
            getList();
        }
    }

    function getList() {
        var entities = [
            { name: 'absenteeism_disability_causes_years', value: $scope.currentCustomer.id }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periods = response.data.data.absenteeism_disability_causes_years;

                if ($scope.periods.length > 0) {
                    $scope.entity.period = $scope.periods[0];
                    $scope.onRefresh();
                    getWorkplaceList();
                }

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function getWorkplaceList() {
        var entities = [
            {
                name: 'absenteeism_disability_workplace_list',
                criteria: {
                    customerId: $scope.currentCustomer.id,
                    period: $scope.entity.period.value
                }
            }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.workplaceList = response.data.data.workplaceList;

                $scope.entity.workplace = null;//$scope.workplaceList.length ? $scope.workplaceList[0] : null;
                $scope.onRefresh();

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function getCharts() {

        if (!$scope.entity.period) {
            return;
        }

        var $criteria = {
            customerId: $scope.currentCustomer.id,
            period: $scope.entity.period ? $scope.entity.period.value : null,
            workplaceId: $scope.entity.workplace ? $scope.entity.workplace.id : null
        };

        var entities = [
            { name: 'chart_bar_with_scales_options', criteria: null },
            { name: 'chart_doughnut_options', criteria: null },
            { name: 'dashboard_occupational_medicine', criteria: $criteria }
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptionsWithScales;
                $scope.chart.bar.options.legend.display = false;
                $scope.chart.bar.options.tooltips = {
                    callbacks: {
                        title: function () {
                            return "";
                        }
                    }
                };

                $scope.chart.doughnut.options = response.data.data.chartDoughnutOptions;
                $scope.chart.doughnut.options.legend.position = 'bottom';

                $scope.chart.data.ausentismVsInvestigationAT = response.data.data.getChartStackedBarAusentismVsInvestigationAT;
                $scope.chart.data.kpis = response.data.data.getKpiOccupationalMedicineDashboard;
                $scope.chart.data.chartPieAbsenteeisByCause = response.data.data.chartPieAbsenteeisByCause;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
