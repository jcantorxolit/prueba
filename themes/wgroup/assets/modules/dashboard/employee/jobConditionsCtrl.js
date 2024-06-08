'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerEmployeeJobConditionsCtrl', function ($scope, $rootScope, $compile,
    DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $localStorage, ChartService, ListService, ModuleListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.periods = [];
        $scope.workplaceList = [];

        $scope.entity = {
            period: null,
            workplace: null
        }

        $scope.chart = {
            bar: {options: null},
            data: {
                levelRisks: {},
            }
        };

        $scope.generalStats = {
            highPriorityPercent: 0,
            mediumPriorityPercent: 0,
            lowPriorityPercent: 0,
            highPriority: 0,
            mediumPriority: 0,
            lowPriority: 0
        };
    };



    /********  LIST  LEVEL RISKS BY MONTHS  *************** */

    $scope.dtInstanceJobConditionsIndicatorsLevelRisksByMonths = {};
    $scope.dtOptionsJobConditionsIndicatorsLevelRisksByMonths = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function(d) {
                d.customerId = $scope.currentCustomer.id;
                d.year = $scope.entity.period ? $scope.entity.period.year : null;
                d.location = $scope.entity.workplace ? $scope.entity.workplace.value : null;
                return JSON.stringify(d);
            },
            url: 'api/customer-jobconditions/indicators/get-level-risks-by-months-list',
            contentType: 'application/json',
            type: 'POST'
        })
        .withDataProp('data')
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function() {
            return $scope.currentCustomer != null;
        })
        .withOption('fnDrawCallback', function() {})
        .withOption('info', false)
        .withOption('ordering', false)
        .withOption('paging', false)
        .withOption('searching', false)
        .withOption('createdRow', function(row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsJobConditionsIndicatorsLevelRisksByMonths = [
        DTColumnBuilder.newColumn('indicator').withTitle("INDICADORES").withOption('width', 200).withOption('defaultContent', ''),
        DTColumnBuilder.newColumn('JAN').withTitle("ENE").withOption('width', 200),
        DTColumnBuilder.newColumn('FEB').withTitle("FEB").withOption('width', 200),
        DTColumnBuilder.newColumn('MAR').withTitle("MAR").withOption('width', 200),
        DTColumnBuilder.newColumn('APR').withTitle("ABR").withOption('width', 200),
        DTColumnBuilder.newColumn('MAY').withTitle("MAY").withOption('width', 200),
        DTColumnBuilder.newColumn('JUN').withTitle("JUN").withOption('width', 200),
        DTColumnBuilder.newColumn('JUL').withTitle("JUL").withOption('width', 200),
        DTColumnBuilder.newColumn('AUG').withTitle("AGO").withOption('width', 200),
        DTColumnBuilder.newColumn('SEP').withTitle("SEP").withOption('width', 200),
        DTColumnBuilder.newColumn('OCT').withTitle("OCT").withOption('width', 200),
        DTColumnBuilder.newColumn('NOV').withTitle("NOV").withOption('width', 200),
        DTColumnBuilder.newColumn('DEC').withTitle("DIC").withOption('width', 200),
    ];


    load();

    $scope.$on('onBroadcastChangeFilterCustomer', function () {
        load();
    });

    $scope.onRefresh = function () {
        getCharts();
        getGeneralStats();
        $scope.dtInstanceJobConditionsIndicatorsLevelRisksByMonths.reloadData();
    };

    $scope.onChangePeriod = function () {
        getWorkplaces();
        $scope.onRefresh();
    };

    $scope.onClearWorkplace = function () {
        $scope.entity.workplace = null;
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
        var entities = [
            { name: 'general_indicators_get_years', customerId: $scope.currentCustomer.id },
        ];

        ModuleListService.getDataList('/customer-jobconditions/config', entities).then(function(response) {
            $scope.periods = response.data.result.yearsGeneralIndicators;

            if ($scope.periods.length > 0) {
                $scope.entity.period = $scope.periods[0];
                getWorkplaces();
                $scope.onRefresh();
            }

            $scope.dtInstanceJobConditionsIndicatorsLevelRisksByMonths.reloadData();
        });
    }


    function getWorkplaces() {
        $scope.entity.workplace = null;

        var entities = [
            { name: 'general_indicators_get_locations', customerId: $scope.currentCustomer.id, year: $scope.entity.period.year },
        ];

        ModuleListService.getDataList('/customer-jobconditions/config', entities).then(function(response) {
            $scope.workplaceList = response.data.result.locationsGeneralIndicators;
        });
    }


    function getCharts() {
        var $criteria = {
            customerId: $scope.currentCustomer.id,
            year: $scope.entity.period ? $scope.entity.period.year : null,
            location: $scope.entity.workplace ? $scope.entity.workplace.value : null
        };

        var entities = [
            {name: 'chart_bar_options', criteria: null},
            {name: 'dashboard_job_condition_indicators', criteria: $criteria}
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptions;
                $scope.chart.bar.options.legend.position = 'bottom';

                $scope.chart.data.levelRisks = response.data.data.customerJobConditionIndicatorsLevelRiskByMonth;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    /********  GENERAL STAST  *************** */
    function getGeneralStats() {
        var $criteria = {
            customerId: $scope.currentCustomer.id,
            year: $scope.entity.period ? $scope.entity.period.year : null,
            location: $scope.entity.workplace ? $scope.entity.workplace.value : null
        };

        var entities = [
            { name: 'customer_job_conditions_intervention_list', criteria: $criteria },
        ];

        ModuleListService.getDataList('/customer-jobconditions/config', entities).then(function(response) {
            $scope.generalStats = response.data.result.jobConditionsInterventionGetStast;
        });
    }

});
