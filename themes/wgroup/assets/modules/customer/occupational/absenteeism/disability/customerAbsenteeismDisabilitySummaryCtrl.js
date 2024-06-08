'use strict';
/**
 * controller for Customers
 */
app.controller('customerAbsenteeismDisabilitySummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', '$document', '$filter', '$aside', 
    'ListService', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ListService, ChartService) {

        var log = $log;
      
        $scope.filter = {
            canCompare: false,
            selectedCause: null,
            selectedYear: null,
            selectedChartCause: null,
            selectedChartYear: null,
            yearList: []
        };

        $scope.compareYearList = [];

        $scope.chart = {
            line: { options: null },            
            months: { data: null },
        };       

        function getCharts() {

            var $compareYearList = $scope.filter.yearList.filter(function (year) {
                return year != null && year.value != null;
              }).map(function(year, index, array) {
                return year.value.value;
            });

            if ($compareYearList === undefined || $compareYearList === null) {
                $compareYearList = [];
            }

            $compareYearList.push($scope.filter.selectedChartYear ? $scope.filter.selectedChartYear.value : 0);

            var $criteria = {
                customerId: $stateParams.customerId,
                cause: $scope.filter.selectedChartCause,
                year: $scope.filter.selectedChartYear ? $scope.filter.selectedChartYear.value : 0,
                yearList:  $compareYearList
            };

            var entities = [           
                {name: 'chart_line_options', criteria: null},                                         
                //{ name: 'customer_absenteeism_disability', criteria: $criteria }
                { name: 'customer_absenteeism_disability_general_event', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.line.options = response.data.data.chartLineOptions;
                    //DB->20190413: Replace customerAbsenteeismDisabilitySummary chart with customerAbsenteeismDisabilityGeneralEvent
                    //$scope.chart.months.data = response.data.data.customerAbsenteeismDisabilitySummary;                                        
                    $scope.chart.months.data = response.data.data.customerAbsenteeismDisabilityGeneralEvent;                                        
                }, function (error) {
                    
                });
        }

        function getList() {
            var entities = [
                {name: 'absenteeism_disability_causes_full'},
                {name: 'current_year'},       
                {name: 'absenteeism_disability_causes_years', value: $stateParams.customerId},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.causes = response.data.data.absenteeism_disability_causes_full;
                    $scope.yearsCause = response.data.data.absenteeism_disability_causes_years;
                    $scope.years = response.data.data.absenteeism_disability_causes_years;
                    var $currentYear = response.data.data.currentYear;

                    var $result = $filter('filter')($scope.years, {value: $currentYear});

                    $scope.filter.selectedChartYear = $result.length ? $result[0] : null;

                    fillCompareYearList();
                    getCharts();

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();


        $scope.dtInstanceSummaryCause = {};
        $scope.dtOptionsSummaryCause = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {
                // Either you specify the AjaxDataProp here
                data: function(d) {                    
                    d.operation = "absenteeism";
                    d.customerId = $stateParams.customerId;
                    d.cause = $scope.filter.selectedCause ? $scope.filter.selectedCause.item : '';
                    d.period = $scope.filter.selectedYear ? $scope.filter.selectedYear.item : '';
                    return JSON.stringify(d);
                },
                url: 'api/customer-absenteeism-disability-sumary',
                contentType: 'application/json',
                type: 'POST',
                beforeSend: function () {
                    // Aqui inicia el loader indicator
                },
                complete: function () {
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true).withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                //log.info("fnPreDrawCallback");
                //Pace.start();
                return true;
            })
            .withOption('fnDrawCallback', function () {

            })
            /*.withDOM("<'row'<'col-xs-5'l><'col-xs-7'f>r><'row'<'col-xs-12't>><'row'<'col-xs-3'i><'col-xs-9'p>>")*/
            .withOption('language', {
                //"url": "//cdn.datatables.net/plug-ins/a5734b29083/i18n/Spanish.json"
            })

            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row, data, dataIndex) {

                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);

            });
        ;

        $scope.dtColumnsSummaryCause = [
            DTColumnBuilder.newColumn('cause').withTitle("Causa").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('quantity').withTitle("Cantidad").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('period').withTitle("Periodo").withOption('defaultContent', ''),
            DTColumnBuilder.newColumn('type').withTitle("Tipo Contracto").withOption('defaultContent', '')
        ];

        $scope.dtInstanceSummaryCauseCallback = function(instance) {
            $scope.dtInstanceSummaryCause = instance;
        };

        $scope.reloadData = function(instance) {
            $scope.dtInstanceSummaryCause.reloadData();
        };

        $scope.onSelecCause = function (item, model) {
            $timeout(function () {                
                $scope.reloadData();
            });
        };

        $scope.onClearCause = function () {
            $timeout(function () {
                $scope.filter.selectedCause = null;                
                $scope.reloadData();
            });
        };

        $scope.onSelecChartCause = function (item, model) {
            $timeout(function () {                
                getCharts();
            });
        };     

        $scope.onClearChartCause = function () {
            $timeout(function () {
                $scope.filter.selectedChartCause = null;                
                getCharts();
            });
        };

        $scope.onSelectYear = function (item, model) {
            $timeout(function () {                
                $scope.reloadData();
            });
        };

        $scope.onClearYear = function () {
            $timeout(function () {
                $scope.filter.selectedYear = null;
                $scope.reloadData();
            });
        };
    
        $scope.onSelectChartYear = function (item, model) {   
            fillCompareYearList();         
            getCharts();
        };

        $scope.onChangeCompare = function() {

        }

        $scope.onAddCompareYear = function() {
            $scope.filter.yearList.push({});
        }

        $scope.onRemoveCompareYear = function(index) {
            $scope.filter.yearList.splice(index, 1);
            getCharts();
        }

        var fillCompareYearList = function() {
            $scope.compareYearList = $scope.years.filter(function($year) {
                return $year.value != ($scope.filter.selectedChartYear ? $scope.filter.selectedChartYear.value : 0);
            });
        }
    }
]);