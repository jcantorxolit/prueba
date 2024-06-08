'use strict';
/**
 * controller for Customers
 */
app.controller('customerDiagnosticSummaryCtrl', ['$scope', '$stateParams', '$log', 'DTOptionsBuilder', 'DTColumnBuilder',
    'DTColumnDefBuilder', '$compile', 'toaster', '$state', '$rootScope', '$timeout', '$http', 'SweetAlert', 'ChartService',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService) {

        var customerId = $stateParams.customerId;
        var currentId = $scope.$parent.currentDiagnostic;

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            programs: { data: null },
            progress: { 
                data: null, 
                total: 0
            }
        };

        getCharts();

        function getCharts() {
            var $criteria = {
                diagnosticId: currentId
            };

            var entities = [           
                {name: 'chart_bar_options', criteria: null},
                {name: 'chart_doughnut_options', criteria: null}, 
                { name: 'customer_diagnostic', criteria: $criteria }
            ];
    
            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.doughnut.options = response.data.data.chartLineOptions; 
                    $scope.chart.programs.data = response.data.data.customerDiagnosticProgram;
                    $scope.chart.progress.data = response.data.data.customerDiagnosticProgress;
                    $scope.chart.progress.total = response.data.data.customerDiagnosticAverage;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }        

        $scope.dtInstanceDiagnosticSummary = {};
		$scope.dtOptionsDiagnosticSummary = DTOptionsBuilder.newOptions()
            // Add Bootstrap compatibility
            .withBootstrap().withOption('responsive', true)
            .withOption('ajax', {                
                data: function(d) {
                    d.operation = 'diagnostic';
                    d.customer_id = customerId;
                    d.diagnostic_id = currentId;
                    return d;
                },
                url: 'api/diagnostic/summary',
                type: 'POST',
                beforeSend: function () {                    
                },
                complete: function (data) {                   
                }
            })
            .withDataProp('data')
            .withOption('order', [[0, 'desc']])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return true;
            })
            .withOption('fnDrawCallback', function () {                
            })
            .withDOM('tr')            
            .withOption('createdRow', function (row, data, dataIndex) {
                // Recompiling so we can bind Angular directive to the DT
                $compile(angular.element(row).contents())($scope);
            });
        ;

        $scope.dtColumnsDiagnosticSummary = [
            DTColumnBuilder.newColumn('abbreviation')
                .withTitle("Código")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('name')
                .withTitle("Programa")
                .withOption('width', 400),

            DTColumnBuilder.newColumn('questions')
                .withTitle("Preguntas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('answers')
                .withTitle("Respuestas")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('advance')
                .withTitle("Avance (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn('average')
                .withTitle("Promedio Total (%)")
                .withOption('width', 200),

            DTColumnBuilder.newColumn(null)
                .withTitle("Estado")
                .withOption('width', 200)
                .renderWith(function (data, type, full, meta) {
                    var label = 'label label-danger';
                    var text = 'Sin Iniciar';

                    if (parseInt(data.answers) == parseInt(data.questions))
                    {
                        text = 'Completado';
                        label = 'label label-info';
                    }
                    else if (parseInt(data.answers) > 0) {
                        text = 'Iniciado';
                        label = 'label label-success';
                    }

                    var status = '<span class="' + label + '">' + text + '</span>';

                    return status;
                })
                .notSortable()
        ];

        $scope.dtInstanceDiagnosticSummaryCallback = function (instance) {
            $scope.dtInstanceDiagnosticSummary = instance;
        };

        $scope.reloadData = function () {
            $scope.dtInstanceDiagnosticSummary.reloadData();
        };

        $scope.onContinue = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("edit", "edit", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.onCancel = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("list", "list");
            }
        };

        $scope.onViewReport = function (index) {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report", "report", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.onViewReporttMonthly = function () {
            if ($scope.$parent != null) {
                $scope.$parent.navToSection("report_monthly", "report_monthly", $scope.$parent.currentDiagnostic);
            }
        };

        $scope.onExportPdf = function()
        {            
            kendo.drawing.drawDOM($(".diagnostic-export-pdf"))
                .then(function(group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group, {
                        paperSize: "auto",
                        margin: { left: "1cm", top: "1cm", right: "1cm", bottom: "1cm" }
                    });
                })
                .done(function(data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "Diagnóstico.pdf",
                        proxyURL: "//demos.telerik.com/kendo-ui/service/export"
                    });
                });
        }

        $scope.onExportExcel = function()
        {
            jQuery("#downloadDocument")[0].src = "api/diagnostic/summary/export-excel?id=" + $scope.$parent.currentDiagnostic;
        }

    }]);