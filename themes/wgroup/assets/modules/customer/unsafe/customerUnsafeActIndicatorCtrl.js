'use strict';
/**
 * controller for Customers
 */
app.controller('customerUnsafeActIndicatorCtrl', ['$scope', '$stateParams', '$log', '$compile', 'toaster', '$state', 
    '$rootScope', '$timeout', '$http', 'SweetAlert', '$aside', 'ListService', 'ChartService',
    function ($scope, $stateParams, $log, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, $aside, ListService, ChartService) {

        $scope.chart = {
            bar: { options: null },
            line: { options: null },
            workplace: { data: null },
            hazard: { data: null },
            period: { data: null },
            status: { data: null },
        }; 
        
        $scope.filter = {
            selectedYear: null,
            selectedMonth: null,
            selectedWorkplace: null
        };

        function getCharts() {
            var $criteria = {
                customerId: $stateParams.customerId,
                year: $scope.filter.selectedYear ? $scope.filter.selectedYear.value : 0,
                month: $scope.filter.selectedMonth ? $scope.filter.selectedMonth.value : null,
                workplace: $scope.filter.selectedWorkplace ? $scope.filter.selectedWorkplace : null,
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_line_options', criteria: null}, 
                { name: 'customer_unsafe_act', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.line.options = response.data.data.chartLineOptions; 

                    $scope.chart.bar.options.legend.position = 'bottom';
                    $scope.chart.line.options.legend.position = 'bottom';

                    $scope.chart.workplace.data = response.data.data.customerUnsafeActWorkplace;
                    $scope.chart.hazard.data = response.data.data.customerUnsafeActHazard;                   
                    $scope.chart.period.data = response.data.data.customerUnsafeActPeriod;
                    $scope.chart.status.data = response.data.data.customerUnsafeActStatus;
                    
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        getList();

        function getList() {

            var $criteria = {
                customerId: $stateParams.customerId,                
            };

            var entities = [
                {name: 'customer_unsafe_act_years', value: null, criteria: $criteria},
                {name: 'customer_unsafe_act_workplace', value: null, criteria: $criteria},                
                {name: 'month_options', value: null},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.years = response.data.data.customerUnsafeActYears;
                    $scope.months = response.data.data.monthOptions;
                    $scope.workplaceList = response.data.data.customerUnsafeActWorkplace;
                    
                    if ($scope.years.length > 0) {
                        $scope.filter.selectedYear = $scope.years[0];
                        getCharts();
                    }
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }
  
        $scope.onExportPdf = function () {
            $timeout(function () {
                kendo.drawing.drawDOM(angular.element(".indicator-unsafe-act-export-pdf"))
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: {left: "1cm", top: "1cm", right: "1cm", bottom: "1cm"}
                    });
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Estadísticas_Condiciones_Inseguras.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
            }, 200);
        }

        $scope.cancelEdition = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list", 0);
            }
        };

        $scope.onSelectYear = function($item, $model) {
            getCharts();
        };

        $scope.onSelectMonth = function($item, $model) {
            getCharts();
        };

        $scope.onSelectWorkPlace = function($item, $model) {            
            getCharts();
        };

        $scope.onClearYear = function() {
            $scope.filter.selectedYear = $scope.years[0];
            getCharts();
        };

        $scope.onClearMonth = function() {
            $scope.filter.selectedMonth = null;
            getCharts();
        };

        $scope.onClearWorkPlace = function() {
            $scope.filter.selectedWorkplace = null;
            getCharts();
        };

    }]);